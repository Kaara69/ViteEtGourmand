<?php
session_start();

include '../include/auth.php';
checkLogin('../login.php');

include '../include/db.php';
$active_page = 'menu'; 

// Initialisation du panier
// Si le panier n’existe pas encore, on crée un tableau vide
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Coordonnées livraison Bordeaux (Bordeaux centre – Place de la Comédie)
define('BDX_LAT',  44.8412);   // latitude
define('BDX_LNG', -0.5712);   // longitude
define('LIV_FIXE', 5.00);     // frais de base (5 €)
define('LIV_KM',   0.54);     // € par km


// AJAX panier (ajouter, modifier, supprimer, vider)
if (isset($_POST['action'])) {
    // On répond en JSON, pas en HTML
    header('Content-Type: application/json');

    $id = (int)($_POST['menu_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Ajouter un menu au panier
    if ($action === 'add') {
        $stmt = $pdo->prepare("
            SELECT id, nom, prix, personnes_min
            FROM menus
            WHERE id = ? AND disponible = 1
        ");
        $stmt->execute([$id]);
        $m = $stmt->fetch();

        if ($m) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['qty']++;
            } else {
                $_SESSION['cart'][$id] = [
                    'id'            => $m['id'],
                    'nom'           => $m['nom'],
                    'prix'          => (float)$m['prix'],
                    'qty'           => 1,
                    'personnes_min' => (int)($m['personnes_min'] ?? 1),
                ];
            }
        }
    }

    // Supprimer un menu
    elseif ($action === 'remove') {
        unset($_SESSION['cart'][$id]);
    }

    //  Mettre à jour la quantité
    elseif ($action === 'update') {
        $qty = max(0, (int)($_POST['qty'] ?? 0));
        if ($qty === 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id]['qty'] = $qty;
        }
    }

    //Vider complètement le panier
    elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    // Calculer sous‑total + nombre d’articles
    $subtotal = 0.0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['prix'] * $item['qty'];
    }

    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['qty'];
    }

    // On renvoie le panier + totaux au JS
    echo json_encode([
        'cart'     => array_values($_SESSION['cart']),
        'subtotal' => $subtotal,
        'count'    => $count,
    ]);
    exit;
}

//Validation et enregistrement de la commande(checkout)
$order_ok = false;
$order_id = null;

if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    // Données du formulaire
    $notes   = trim($_POST['notes']           ?? '');
    $date_ev = trim($_POST['date_evenement']  ?? '');

    // On valide la date : doit être dans le futur
    if ($date_ev && (strtotime($date_ev) === false || strtotime($date_ev) < strtotime('today'))) {
        $date_ev = '';
    }

    $nb_pers       = max(1, (int)($_POST['nb_personnes']       ?? 1));
    $adresse_liv   = trim($_POST['adresse_livraison']          ?? '');
    $km            = max(0, (float)($_POST['km_livraison']     ?? 0));
    $frais_liv     = max(LIV_FIXE, round(LIV_FIXE + $km * LIV_KM, 2));

    // Calcul du sous‑total et de la remise
    $subtotal = 0.0;
    $remise   = 0.0;

    foreach ($_SESSION['cart'] as $item) {
        $line = $item['prix'] * $item['qty'];
        $subtotal += $line;

        $pmin = (int)($item['personnes_min'] ?? 1);
        if ($pmin > 1 && $nb_pers >= $pmin + 5) {
            $remise += round($line * 0.10, 2);
        }
    }

    $total = round($subtotal - $remise + $frais_liv, 2);

    // Enregistrer la commande
    $pdo->prepare("
        INSERT INTO commandes
        (user_id, total, notes, nb_personnes, adresse_livraison,
         km_livraison, frais_livraison, remise, date_evenement)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $_SESSION['user_id'], $total, $notes, $nb_pers,
        $adresse_liv, $km, $frais_liv, $remise,
        $date_ev ?: null
    ]);

    $cid = (int)$pdo->lastInsertId();

    //Enregistrer les lignes de commande (menus commandés)
    $stmt = $pdo->prepare("
        INSERT INTO commande_items
        (commande_id, menu_id, nom_menu, quantite, prix_unitaire)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($_SESSION['cart'] as $item) {
        $stmt->execute([$cid, $item['id'], $item['nom'], $item['qty'], $item['prix']]);
    }

    // Vider le panier après la commande
    $_SESSION['cart'] = [];

    // Synchroniser les stats (NoSQL)
    include_once '../includes/nosql_db.php';
    $statsSync = new StatsSync($pdo, new NoSQLStore());
    $statsSync->syncOrder($cid);

    $order_ok = true;
    $order_id = $cid;
}


// Chargement des menus et regroupement par cat.
$menus = $pdo->query("
    SELECT *
    FROM menus
    WHERE disponible = 1
    ORDER BY categorie, nom
")->fetchAll();

$by_cat = [];
foreach ($menus as $m) {
    $by_cat[$m['categorie']][] = $m;
}


// Charger l’adresse de l’utilisateur (profil)
$user = $pdo->prepare("SELECT adresse FROM users WHERE id = ?");
$user->execute([$_SESSION['user_id']]);
$adresse_client = $user->fetchColumn() ?? '';


// Styles visuels par catégorie de menu
$cat_style = [
    'Menu Classique' => ['🍽️', '#2D4A3E'],
    'Menu Noël'      => ['🎄', '#6B1515'],
    'Menu Pâques'    => ['🐣', '#3D5A1E'],
    'Menu Mariage'   => ['💍', '#4A2D6B'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander – Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/espace.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'include/partials/user_nav.php'; ?>

    <div class="container-fluid py-4 px-3 px-lg-4">
        <!-- affichage du message de confirmation de cmd -->
         <?php if ($order_ok): ?>
        <div class="alert alert-success alert dismissible d-flex align-items-center gap-2 mx-2">
            <i class="bi bi-check-circle-fill"></i>
            Commande <strong>#<?= $order_id ?></strong> passée avec succès !
            <a href="orders.php" class="alert-link ms-2">Suivre →</a>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- carte des menus -->
             <div class="col-lg-7 col-xl-8">
                <h4 class="fw-bold mb-4">Notre carte</h4>
                    <?php foreach ($by_cat as $cat => $items): ?>
                    <?php
                    // Couleur + emoji de la catégorie
                    $cs = $cat_style[$cat] ?? ['🍽️', '#2D4A3E'];
                    ?>
                <div class="cat-header" style="background: <?= $cs[1]?>;">
                    <?= $cs[0] ?> <?= htmlspecialchars($cat) ?>
                </div>
                <div class="row g-3 mb-4">
                    <?php foreach ($items as $m): ?>
                        <?php
                        // Valeurs utiles pour la carte
                        $pers = (int)($m['personnes_min'] ?? 1);
                        $ppp  = $pers > 1 ? round($m['prix'] / $pers, 2) : null;
                        // Régime et allergènes
                        $rgs  = array_filter(array_map('trim', explode(',', $m['regime']    ?? '')));
                        $als  = array_filter(array_map('trim', explode(',', $m['allergenes'] ?? '')));
                            ?>
                    <div class="col-md-6">
                        <div class="menu-card shadow-sm h-100">
                            <?php if (!empty($m['image_url'])): ?>
                                <img src="<?= htmlspecialchars($m['image_url']) ?>"class="menu-img"alt="<?= htmlspecialchars($m['nom']) ?>"
                                loading="lazy">
                            <?php else: ?>
                            <div class="menu-img-ph" style="background:<?= $cs[1] ?>;"><?= $cs[0] ?></div>
                            <?php endif; ?>

                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <div class="fw-bold" style="line-height:1.3;font-size:.95rem;">
                                        <?= htmlspecialchars($m['nom']) ?>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <?php if ($ppp): ?>
                                        <div class="prix-tag"><?= number_format($ppp, 2, ',', ' ') ?> €<span style="font-size:.62rem;font-weight:400;color:#999;">/pers.</span></div>
                                        <div style="font-size:.68rem;color:#aaa;">Total : <?= number_format($m['prix'], 2, ',', ' ') ?> €</div>
                                        <?php else: ?>
                                        <div class="prix-tag"><?= number_format($m['prix'], 2, ',', ' ') ?> €</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php if ($pers > 1): ?>
                                    <span class="pers-tag" style="background:<?= $cs[1] ?>;color:#fff;">
                                    👥 À partir de <?= $pers ?> personnes
                                    </span>
                                    <span class="pers-tag" style="background:#FFF3E0;color:#E65100;" title="-10% si vous commandez pour <?= $pers + 5 ?> personnes ou plus">
                                    🏷️ -10% dès <?= $pers + 5 ?> pers.
                                    </span>
                                    <?php endif; ?>
                                    <?php if (in_array('vegan', $rgs)): ?>
                                    <span class="pers-tag" style="background:#E8F5E9;color:#1B5E20;">🌱 Vegan</span>
                                    <?php elseif (in_array('vegetarien', $rgs)): ?>
                                    <span class="pers-tag" style="background:#E8F5E9;color:#1B5E20;">🥗 Végé</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted small mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5;">
                                    <?= htmlspecialchars($m['description']) ?>
                                </p>
                                <div class="d-flex gap-2">
                                    <!-- Bouton Détails (ouvre le modal) -->
                                    <button class="btn btn-outline-dark btn-sm flex-grow-1 btn-detail"
                                            data-id="<?= $m['id'] ?>"
                                            data-nom="<?= htmlspecialchars($m['nom'], ENT_QUOTES) ?>"
                                            data-desc="<?= htmlspecialchars($m['description'], ENT_QUOTES) ?>"
                                            data-prix="<?= number_format($m['prix'], 2, ',', ' ') ?>"
                                            data-ppp="<?= $ppp ? number_format($ppp, 2, ',', ' ') : '' ?>"
                                            data-pers="<?= $pers ?>"
                                            data-cat="<?= htmlspecialchars($cat, ENT_QUOTES) ?>"
                                            data-img="<?= htmlspecialchars($m['image_url'] ?? '', ENT_QUOTES) ?>"
                                            data-allergens="<?= htmlspecialchars($m['allergenes'] ?? '', ENT_QUOTES) ?>"
                                            data-regimes="<?= htmlspecialchars($m['regime'] ?? '', ENT_QUOTES) ?>"
                                            data-color="<?= $cs[1] ?>"
                                            data-emoji="<?= $cs[0] ?>">
                                    <i class="bi bi-eye me-1"></i>Détails
                                    </button>
                                    <!-- Bouton Ajouter au panier -->
                                    <button class="btn fw-bold text-white btn-add btn-sm flex-grow-1"
                                            data-id="<?= $m['id'] ?>"
                                            data-pmin="<?= $pers ?>"
                                            style="background:<?= $cs[1] ?>;">
                                    <i class="bi bi-cart-plus me-1"></i>Ajouter
                                    </button>
                                </div> <!-- d-flex -->
                            </div> <!-- p-3 -->
                        </div> <!-- menu-card -->
                    </div> <!-- col-md-6 -->
                        <?php endforeach; ?>
                </div> <!-- row g-3 -->
                        <?php endforeach; ?>
             </div> <!-- col lg-7 -->


             <!-- PANIER -->
            <div class="col-lg-5">
                <div class="cart-panel">
                    <div class="card border-0 shadow">
                                        <!-- En tête panier -->
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: var(--dark);
                            color:#fff;border-radius:12px 12px 0 0;">
                            <span class="fw-bold"><i class="bi bi-cart3 me-2"></i>Mon panier</span>
                            <span class="badge rounded-pill" style="background:var(--gold);" id="cart-count">0</span>
                        </div>
                                        <!-- contenu panier -->
                        <div class="card-body px-3 py-3">
                            <div id="cart-items">
                                <p class="text-muted text-center py-3 small mb-0"> Votre panier est vide.</p>
                            </div>
                        
                                        <!-- Adresse de livraispn -->
                            <div class="mt-3">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-geo-alt-fill me-1" style="color:var(--gold);"></i>
                                    Adresse de livraison
                                </label>
                                <div class="liv-input-wrap" style="position:relative;">
                                    <input type="text" id="adresse-input" class="form-control form-control-sm" 
                                    placeholder="Ex: 6 rue de la Paix, Bordeaux" value="<?=htmlspecialchars($adresse_client) ?>" autocomplête="off">
                                    <div id="spinner-liv" class="spinner">
                                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                    </div>
                                    <div id="adresse-suggestion"></div>
                                </div>
                                <div id="liv-result" class="liv-result" style="display:none"></div>
                                <div class="text-muted" style="font-size:.68rem;margin-top:.25rem;">
                                    <i class="bi bi-info-circle me-1">5 € fixes + 0,54 €/km depuis Bordeaux centre</i>
                                </div>
                            </div>

                                        <!-- form caché jusqu'à ce qu'il y est article dans la cmd -->
                            <div class="d-none" id="order-form">
                                <hr class="my-3">
                                            <!-- Nbre de personnes -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">
                                        <i class="bi bi-people-fill me-1" style="color:var(--gold);"></i>Nombre de personnes
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <button class="btn btn-outline-secondary" id="pers-minus" type="button">−</button>
                                        <input type="number" id="nb-personnes" class="form-control text-center fw-bold" value="1" min="1" max="999">
                                        <button class="btn btn-outline-secondary" id="pers-plus" type="button">−</button>
                                    </div>
                                    <div id="discount-info" class="mt-1 small text-success d-none">
                                        <i class="bi bi-tag-fill me-1"></i>Remise de 10% appliquée sur certains menus !
                                    </div>
                                </div>

                                                <!-- Récap -->
                                <div class="bg-light rounded-3 p-3 mb-3">
                                    <div class="summary-row">
                                        <span>Sous-total</span>
                                        <span id="sum-subtotal">0,00 €</span>
                                    </div>
                                    <div class="summary-row discount d-none" id="sum-remise-row">
                                        <span>🏷️ Remise fidélité</span>
                                        <span id="sum-remise">- 0,00 €</span>
                                    </div>
                                    <div class="summary-row livraison">
                                        <span>
                                            🚚 Livraison
                                            <span id="sum-km-label" class="text-muted" style="font-size:.75rem"></span>
                                        </span>
                                        <span id="sum-livraison">5,00 €</span>
                                    </div>
                                    <div class="summary-row total">
                                        <span>Total TTC</span>
                                        <span id="sum-total" style="color:var(--gold);">5,00 €</span>
                                    </div>
                                </div>

                                            <!-- Formulaire caché + notes + valider -->
                                <form method="post" id="checkout-form">
                                    <input type="hidden" name="checkout"                value="1">
                                    <input type="hidden" name="nb_personnes"            value="1" id="input-nb-personnes">
                                    <input type="hidden" name="adresse_livraison"       value=""  id="input-adresse">
                                    <input type="hidden" name="km_livraison"            value="0" id="input-km">
                                
                                
                                            <!-- Date de l'event -->
                                <div class="mb-2">
                                    <label class="form-label small fw-bold mb-1" style="color:var(--gold)">
                                        <i class="bi bi-calendar-event me-1"></i>Date de l'événement
                                    </label>
                                    <input type="date" name="date_evenement" id="input-date-evenement" class="form-control form-control-sm"
                                    min="<?= date('Y-m-d' , strtotime('+1 day')) ?>" placeholder="JJ/MM/AAAA">
                                    <div class="form-text" style="font-size:.72rem;color:#999;">
                                        Pour quel jour souhaitez-vous votre commande ?
                                    </div>
                                </div>

                                <textarea name="notes" class="form-control form-control-sm mb-2" rows="2"
                                        placeholder="Instructions spéciales, allergies, horaire souahité..."></textarea>
                                <button class="btn w-100 fw-bold text-white" type="submit" style="background:var(--dark);">
                                    <i class="bi bi-check-lg me-1"></i>Valider la commande
                                </button>
                                </form>
                                <button class="btn btn-outline-danger btn-sm w-100 mt-2" id="btn-clear">
                                    <i class="bi bi-trash me-1"></i>Vider le panier
                                </button>
                            </div> <!-- d-none -->
                        </div> <!--card-body-->
                    </div> <!--card border-0 -->                        
                </div> <!-- cart-panel -->
            </div> <!-- col-lg-5 -->
        </div> <!-- row g-4-->
    </div> <!-- container -->
    
</body>
</html>