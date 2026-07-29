<?php
session_start();
include __DIR__ . '/../config.php';
include __DIR__ . '/../includes/auth.php';
checkLogin('../login.php');

include __DIR__ . '/../includes/db.php';
$active_page = 'menu'; 

// Initialisation du panier
// Si le panier n’existe pas encore, on crée un tableau vide

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];


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
                    'prix'          => $m['prix'],
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
    $notes   = trim($_POST['notes']          ?? '');
    $date_ev = trim($_POST['date_evenement'] ?? '');
    $heure_ev   = trim($_POST['heure_evenement'] ?? '');

    // On valide la date : doit être dans le futur
    // if ($date_ev && (strtotime($date_ev) === false || strtotime($date_ev) < strtotime('today'))) {
    //     $date_ev = '';
    // }
    // Validation de la date
if ($date_ev && (strtotime($date_ev) === false || strtotime($date_ev) < strtotime('today'))) {
    $date_ev = '';
}

// Validation de l'heure
if ($heure_ev && strtotime($heure_ev) === false) {
    $heure_ev = '';
}

    $nb_pers     = max(1, (int)($_POST['nb_personnes']  ?? 1));
    $adresse_liv = trim($_POST['adresse_livraison']     ?? '');
    $km          = max(0, (float)($_POST['km_livraison'] ?? 0));
    $frais_liv   = max(LIV_FIXE, round(LIV_FIXE + $km * LIV_KM, 2));
// Correction pb total avec personne_min 
    // Calcul du sous-total et de la remise
    $subtotal = 0.0;
    $remise   = 0.0;
    foreach ($_SESSION['cart'] as $item) {
        $pmin = (int)($item['personnes_min'] ?? 1);
        $line = $pmin > 1
            ? ($item['prix'] / $pmin) * $nb_pers * $item['qty']
            : $item['prix'] * $item['qty'];
        $subtotal += $line;
        if ($pmin > 1 && $nb_pers >= $pmin + 5) {
            $remise += round($line * 0.10, 2);
        }
    }
    $total = round($subtotal - $remise + $frais_liv, 2);

    // Enregistrer la commande
    // $pdo->prepare("
    //     INSERT INTO commandes
    //     (user_id, total, notes, nb_personnes, adresse_livraison,
    //      km_livraison, frais_livraison, remise, date_evenement)
    //     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    // ")->execute([
    //     $_SESSION['user_id'], $total, $notes, $nb_pers,
    //     $adresse_liv, $km, $frais_liv, $remise,
    //     $date_ev ?: null
    // ]);
    $pdo->prepare("
    INSERT INTO commandes
    (user_id, total, notes, nb_personnes, adresse_livraison,
     km_livraison, frais_livraison, remise,
     date_evenement, heure_evenement)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
")->execute([
    $_SESSION['user_id'], $total, $notes, $nb_pers,
    $adresse_liv, $km, $frais_liv, $remise,
    $date_ev ?: null,
    $heure_ev ?: null
]);

    $cid = (int)$pdo->lastInsertId();

    // Enregistrer les lignes de commande avec personnes_min
    $stmt = $pdo->prepare("
        INSERT INTO commande_items
        (commande_id, menu_id, nom_menu, quantite, prix_unitaire, personnes_min)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($_SESSION['cart'] as $item) {
        $stmt->execute([
            $cid,
            $item['id'],
            $item['nom'],
            $item['qty'],
            $item['prix'],
            $item['personnes_min'] ?? 1
        ]);
    }

    // Vider le panier
    $_SESSION['cart'] = [];

    // Synchroniser les stats (NoSQL)
    include_once __DIR__ . '/../includes/nosql_db.php';
    $statsSync = new StatsSync($pdo, new NoSQLStore());
    $statsSync->syncOrder($cid);

    // Envoi email de confirmation
    include_once __DIR__ . '/../includes/mailer.php';

    $stmt_user = $pdo->prepare("SELECT email, prenom FROM users WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $client = $stmt_user->fetch();

    $stmt_items = $pdo->prepare("
        SELECT nom_menu, quantite, prix_unitaire
        FROM commande_items
        WHERE commande_id = ?
    ");
    $stmt_items->execute([$cid]);
    $items_email = $stmt_items->fetchAll();

    $lignes_email = '';
    foreach ($items_email as $item) {
        $ligne_total   = number_format($item['quantite'] * $item['prix_unitaire'], 2, ',', ' ');
        $lignes_email .= "
        <tr>
            <td style='padding:8px;border-bottom:1px solid #f0ebe3;'>{$item['nom_menu']}</td>
            <td style='padding:8px;border-bottom:1px solid #f0ebe3;text-align:center;'>{$item['quantite']}</td>
            <td style='padding:8px;border-bottom:1px solid #f0ebe3;text-align:right;'>{$ligne_total} €</td>
        </tr>";
    }

    // $date_affiche = $date_ev ? date('d/m/Y', strtotime($date_ev)) : 'Non précisée';
    $date_affiche = $date_ev
    ? date('d/m/Y', strtotime($date_ev))
    : 'Non précisée';

if ($heure_ev) {
    $date_affiche .= ' à ' . date('H:i', strtotime($heure_ev));
}

    $contenu_email = "
    <div style='font-family:Nunito,sans-serif;max-width:600px;margin:0 auto;background:#FAF7F2;'>
        <div style='background:#1C1510;padding:2rem;text-align:center;'>
            <h1 style='color:#C9973D;font-family:Georgia,serif;margin:0;font-size:1.8rem;'>
                Vite &amp; Gourmand
            </h1>
            <p style='color:rgba(255,255,255,.6);margin:.5rem 0 0;font-size:.9rem;'>
                Confirmation de commande
            </p>
        </div>
        <div style='padding:2rem;'>
            <p style='font-size:1.1rem;'>Bonjour <strong>{$client['prenom']}</strong>,</p>
            <p style='color:#555;line-height:1.7;'>
                Votre commande <strong>#$cid</strong> a bien été enregistrée.
                Notre équipe va l'examiner et vous confirmera sa prise en charge très prochainement.
            </p>
            <div style='background:#fff;border-radius:8px;padding:1.5rem;margin:1.5rem 0;border:1px solid rgba(201,151,61,.2);'>
                <h3 style='color:#1C1510;font-family:Georgia,serif;margin:0 0 1rem;font-size:1.1rem;'>Récapitulatif</h3>
                <table style='width:100%;border-collapse:collapse;'>
                    <thead>
                        <tr style='background:#1C1510;color:#C9973D;'>
                            <th style='padding:8px;text-align:left;font-size:.85rem;'>Menu</th>
                            <th style='padding:8px;text-align:center;font-size:.85rem;'>Qté</th>
                            <th style='padding:8px;text-align:right;font-size:.85rem;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>$lignes_email</tbody>
                </table>
                <div style='margin-top:1rem;padding-top:1rem;border-top:2px solid rgba(201,151,61,.3);'>
                    <div style='display:flex;justify-content:space-between;margin-bottom:.4rem;font-size:.9rem;color:#555;'>
                        <span>Sous-total</span>
                        <span>" . number_format($subtotal, 2, ',', ' ') . " €</span>
                    </div>
                    " . ($remise > 0 ? "
                    <div style='display:flex;justify-content:space-between;margin-bottom:.4rem;font-size:.9rem;color:#2E7D32;'>
                        <span>Remise</span>
                        <span>- " . number_format($remise, 2, ',', ' ') . " €</span>
                    </div>" : "") . "
                    <div style='display:flex;justify-content:space-between;margin-bottom:.4rem;font-size:.9rem;color:#555;'>
                        <span>Livraison</span>
                        <span>" . number_format($frais_liv, 2, ',', ' ') . " €</span>
                    </div>
                    <div style='display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;color:#C9973D;margin-top:.5rem;'>
                        <span>Total TTC</span>
                        <span>" . number_format($total, 2, ',', ' ') . " €</span>
                    </div>
                </div>
            </div>
            <div style='background:#fff;border-radius:8px;padding:1.5rem;border:1px solid rgba(201,151,61,.2);'>
                <h3 style='color:#1C1510;font-family:Georgia,serif;margin:0 0 1rem;font-size:1.1rem;'>Informations de livraison</h3>
                <p style='margin:.3rem 0;font-size:.9rem;color:#555;'>📅 <strong>Date :</strong> $date_affiche</p>
                <p style='margin:.3rem 0;font-size:.9rem;color:#555;'>📍 <strong>Adresse :</strong> $adresse_liv</p>
                " . ($notes ? "<p style='margin:.3rem 0;font-size:.9rem;color:#555;'>📝 <strong>Notes :</strong> $notes</p>" : "") . "
            </div>
            <div style='text-align:center;margin-top:1.5rem;'>
                <a href='<?= BASE_URL ?>user/orders.php'
                   style='background:#C9973D;color:#1C1510;padding:.75rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;'>
                    Suivre ma commande →
                </a>
            </div>
        </div>
        <div style='background:#1C1510;padding:1.5rem;text-align:center;'>
            <p style='color:rgba(255,255,255,.4);font-size:.8rem;margin:0;'>
                © " . date('Y') . " Vite &amp; Gourmand — 12 rue des Saveurs, 33000 Bordeaux
            </p>
        </div>
    </div>";

    envoyerEmail($client['email'], "Confirmation de votre commande #$cid — Vite & Gourmand", $contenu_email);

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

<?php include __DIR__ . '/../includes/partials/user_nav.php'; ?>

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
                                <img src="<?= BASE_URL . htmlspecialchars($m['image_url']) ?>" class="menu-img" alt="<?= htmlspecialchars($m['nom'])  ?>"loading="lazy">
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
                                            data-img="<?= htmlspecialchars(BASE_URL . ($m['image_url'] ?? ''), ENT_QUOTES) ?>"
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
            <div class="col-lg-4">
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
                        
                                  <!-- ── Adresse livraison — toujours visible ── -->
                            <div class="mt-3">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-geo-alt-fill me-1" style="color:var(--gold);"></i>Adresse de livraison
                                </label>
                            <div class="liv-input-wrap" style="position:relative;">
                                <input type="text" id="adresse-input" class="form-control form-control-sm"
                                    placeholder="Ex : 5 rue de la Paix, Bordeaux"
                                    value="<?= htmlspecialchars($adresse_client) ?>"
                                    autocomplete="off">
                                <div id="spinner-liv" class="spinner">
                                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                </div>
                                <div id="adresse-suggestions"></div>
                            </div>
                            <div id="liv-result" class="liv-result" style="display:none;"></div>
                                <div class="text-muted" style="font-size:.68rem;margin-top:.25rem;">
                                    <i class="bi bi-info-circle me-1"></i>5 € fixes + 0,54 €/km depuis Bordeaux centre
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
                                        <button class="btn btn-outline-secondary" id="pers-plus" type="button">+</button>
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

                                    <input type="date"
                                        name="date_evenement"
                                        id="input-date-evenement"
                                        class="form-control form-control-sm"
                                        min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                        placeholder="JJ/MM/AAAA">

                                    <div class="form-text" style="font-size:.72rem;color:#999;">
                                        Pour quel jour souhaitez-vous votre commande ?
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small fw-bold mb-1" style="color:var(--gold)">
                                        <i class="bi bi-clock me-1"></i>Heure de l'événement
                                    </label>

                                    <input type="time"
                                        name="heure_evenement"
                                        id="input-heure-evenement"
                                        class="form-control form-control-sm"
                                        min="10:00"
                                        max="22:00"
                                        step="900">

                                    <div class="form-text" style="font-size:.72rem;color:#999;">
                                        À quelle heure souhaitez-vous être livré(e) ?
                                    </div>
                                </div>

                                <textarea name="notes" class="form-control form-control-sm mb-2" rows="2"
                                        placeholder="Instructions spéciales, allergies, horaire souhaité..."></textarea>
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

<script>
// ── Constantes livraison
const BDX_LAT  = <?= BDX_LAT ?>;
const BDX_LNG  = <?= BDX_LNG ?>;
const LIV_FIXE = <?= LIV_FIXE ?>;
const LIV_KM   = <?= LIV_KM ?>;

// ── État
let cart        = <?= json_encode(array_values($_SESSION['cart'])) ?>;
let nbPersonnes = 1;
let nbPersonnesManuel = false;
let kmLivraison = 0;      // distance calculée en km
let adresseLiv  = '';     // adresse textuelle

// ── Haversine distance (km)
function haversine(lat1, lng1, lat2, lng2) {
  const R  = 6371;
  const dL = (lat2 - lat1) * Math.PI / 180;
  const dl = (lng2 - lng1) * Math.PI / 180;
  const a  = Math.sin(dL/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dl/2)**2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function calcFraisLiv(km) {
  return Math.max(LIV_FIXE, Math.round((LIV_FIXE + km * LIV_KM) * 100) / 100);
}

// ── Formatage 
function fmt(n) { return n.toFixed(2).replace('.', ',') + ' €'; }
function fmtKm(n){ return n < 1 ? '< 1 km' : Math.round(n) + ' km'; }

// ── Recalcul totaux (debug prix/personnes)

function calcTotals() {

    let subtotal = 0, remise = 0;
    cart.filter(i => i.qty > 0).forEach(i => {
        const pmin = parseInt(i.personnes_min) || 1;
console.log(i.nom, {
    prix: i.prix,
    personnes_min: i.personnes_min,
    pmin,
    qty: i.qty
});
        // Menu collectif (ex: 750€ pour 20 pers min)
        // → prix par personne × nb personnes choisi
        // Menu individuel (ex: 19.90€/pers)
        // → prix × quantité
        const line = pmin > 1
            ? (i.prix / pmin) * nbPersonnes * i.qty
            : i.prix * i.qty;

        subtotal += line;

        // Remise 10% si nb personnes >= min + 5
        if (pmin > 1 && nbPersonnes >= pmin + 5) {
            remise += Math.round(line * 0.10 * 100) / 100;
        }
    });

    const livraison = calcFraisLiv(kmLivraison);
    return { subtotal, remise, livraison, total: subtotal - remise + livraison };
}

// ── Rendu panier
function renderCart() {
  const items  = cart.filter(i => i.qty > 0);
  const count  = items.reduce((s,i) => s + i.qty, 0);
  const totals = calcTotals();
  document.getElementById('cart-count').textContent = count;

  const itemsEl = document.getElementById('cart-items');
  const orderEl = document.getElementById('order-form');
  if (!items.length) {
    itemsEl.innerHTML = '<p class="text-muted text-center py-3 small mb-0">Votre panier est vide.</p>';
    orderEl.classList.add('d-none'); return;
  }
  orderEl.classList.remove('d-none');

  // Si un km est déjà calculé (adresse pré-remplie), réafficher le résultat
  if (kmLivraison > 0 && livResultEl.style.display === 'none') {
    showLivResult(kmLivraison, adresseLiv);
  }

  let html = '';
  items.forEach(i => {
    const pmin       = parseInt(i.personnes_min) || 1;
    const hasDisc    = pmin > 1 && nbPersonnes >= pmin + 5;
    const lineTotal  = pmin > 1
    ? (i.prix / pmin) * nbPersonnes * i.qty
    : i.prix * i.qty;
const lineRemise = hasDisc ? Math.round(lineTotal * 0.10 * 100) / 100 : 0;
    html += `
    <div class="cart-item">
      <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="flex-grow-1">
          <div class="small fw-semibold">${i.nom}${hasDisc ? '<span class="discount-badge ms-1">-10%</span>' : ''}</div>
         
        </div>
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
          <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="cartAction({action:'update',menu_id:${i.id},qty:${i.qty-1}})">−</button>
          <span class="px-1 fw-semibold small">${i.qty}</span>
          <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="cartAction({action:'update',menu_id:${i.id},qty:${i.qty+1}})">+</button>
          <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="cartAction({action:'remove',menu_id:${i.id}})">✕</button>
        </div>
      </div>
      <div class="text-end small fw-bold mt-1" style="color:var(--gold);">
        ${lineRemise > 0
          ? `<span class="text-muted text-decoration-line-through me-1">${fmt(lineTotal)}</span>${fmt(lineTotal - lineRemise)}`
          : fmt(lineTotal)}
      </div>
    </div>`;
  });
  itemsEl.innerHTML = html;

  // Récapitulatif
  document.getElementById('sum-subtotal').textContent  = fmt(totals.subtotal);
  document.getElementById('sum-livraison').textContent = fmt(totals.livraison);
  document.getElementById('sum-total').textContent     = fmt(totals.total);

  const kmLabel = document.getElementById('sum-km-label');
  kmLabel.textContent = kmLivraison > 0 ? `(${fmtKm(kmLivraison)})` : '';

  const remRow = document.getElementById('sum-remise-row');
  const discInfo = document.getElementById('discount-info');
  if (totals.remise > 0) {
    document.getElementById('sum-remise').textContent = '- ' + fmt(totals.remise);
    remRow.classList.remove('d-none');
    discInfo.classList.remove('d-none');
  } else {
    remRow.classList.add('d-none');
    discInfo.classList.add('d-none');
  }

  // Sync inputs cachés
  document.getElementById('input-nb-personnes').value = nbPersonnes;
  document.getElementById('input-km').value           = kmLivraison;
  document.getElementById('input-adresse').value      = adresseLiv;
}

// AJAX panier
async function cartAction(data) {
  const fd = new FormData();
  for (const [k,v] of Object.entries(data)) fd.append(k, v);
  const res = await fetch('menu.php', {method:'POST', body:fd});
  const text = await res.text();
  console.log('RÉPONSE BRUTE:', text);
  const d = JSON.parse(text);
  cart = d.cart;
  updateNbPersonnesUI();
  renderCart();
}
document.querySelectorAll('.btn-add').forEach(btn => {
  btn.addEventListener('click', () => cartAction({action:'add', menu_id:btn.dataset.id}));
});
document.getElementById('btn-clear').addEventListener('click', () => {
  nbPersonnesManuel = false;
  cartAction({action:'clear', menu_id:0});
});

// ── Nombre de personnes
const nbInput = document.getElementById('nb-personnes');

// Calcule le minimum requis par les menus dans le panier
function getCartMin() {
  return cart.filter(i => i.qty > 0)
    .reduce((max, i) => Math.max(max, parseInt(i.personnes_min) || 1), 1);
}

function updateNbPersonnesUI() {
  const min = getCartMin();

  if (nbPersonnesManuel) {
    if (nbPersonnes < min) {
      nbPersonnes = min;
    }
  } else {
    nbPersonnes = min;
  }

  nbInput.value = nbPersonnes;
  nbInput.min = min;

  let hint = document.getElementById('pers-min-hint');
  if (!hint) {
    hint = document.createElement('div');
    hint.id = 'pers-min-hint';
    hint.className = 'mt-1 small';
    nbInput.closest('.mb-3').appendChild(hint);
  }
  if (min > 1) {
    hint.innerHTML = `<i class="bi bi-info-circle me-1" style="color:var(--gold);"></i>Minimum <strong>${min} personnes</strong> requis pour ce menu`;
    hint.style.color = '#C9973D';
  } else {
    hint.innerHTML = '';
  }

  document.getElementById('pers-minus').disabled = (nbPersonnes <= min);
}

document.getElementById('pers-minus').addEventListener('click', () => {
    nbPersonnesManuel = true;
  const min = getCartMin();
  nbPersonnes = Math.max(min, nbPersonnes - 1);
  nbInput.value = nbPersonnes;
  updateNbPersonnesUI();
  renderCart();
});
document.getElementById('pers-plus').addEventListener('click', () => {
    nbPersonnesManuel = true;
  nbPersonnes = Math.min(999, nbPersonnes + 1);
  nbInput.value = nbPersonnes;
  updateNbPersonnesUI();
  renderCart();
});
nbInput.addEventListener('input', () => {
    nbPersonnesManuel = true;
  const min = getCartMin();
  nbPersonnes = Math.max(min, parseInt(nbInput.value) || min);
  nbInput.value = nbPersonnes;
  updateNbPersonnesUI();
  renderCart();
});

//  Nominatim autocomplete + géocodage 
const adresseInput   = document.getElementById('adresse-input');
const suggestionsEl  = document.getElementById('adresse-suggestions');
const livResultEl    = document.getElementById('liv-result');
const spinnerEl      = document.getElementById('spinner-liv');
let nominatimTimer   = null;
let isGeocoding      = false;

function showLivResult(km, label) {
  const frais = calcFraisLiv(km);
  if (km === 0) {
    livResultEl.className = 'liv-result bordeaux';
    livResultEl.innerHTML = `✅ <strong>Bordeaux centre</strong> — Frais de livraison : <strong>${fmt(frais)}</strong>`;
  } else {
    livResultEl.className = 'liv-result ok';
    livResultEl.innerHTML = `📍 <strong>${fmtKm(km)}</strong> de Bordeaux centre — Frais : <strong>${fmt(frais)}</strong>`;
  }
  livResultEl.style.display = '';
}

async function fetchSuggestions(query) {
  if (query.length < 4) { suggestionsEl.innerHTML = ''; return; }
  try {
    const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query + ', France')}&format=json&limit=5&addressdetails=1&countrycodes=fr`;
    const results = await (await fetch(url, {headers:{'Accept-Language':'fr'}})).json();

    if (!results.length) { suggestionsEl.innerHTML = '<div class="sugg-item text-muted">Aucune adresse trouvée</div>'; return; }
    suggestionsEl.innerHTML = results.map((r,i) =>
      `<div class="sugg-item" data-idx="${i}" data-lat="${r.lat}" data-lng="${r.lon}" data-label="${r.display_name.replace(/"/g,'&quot;')}">
         📍 ${r.display_name.split(',').slice(0,3).join(', ')}
       </div>`
    ).join('');

    suggestionsEl.querySelectorAll('.sugg-item').forEach(el => {
      el.addEventListener('click', () => {
        const lat = parseFloat(el.dataset.lat);
        const lng = parseFloat(el.dataset.lng);
        const label = el.dataset.label;
        adresseInput.value = el.textContent.trim().replace('📍 ','');
        adresseLiv = label;
        suggestionsEl.innerHTML = '';
        kmLivraison = haversine(BDX_LAT, BDX_LNG, lat, lng);
        showLivResult(kmLivraison, label);
        renderCart();
      });
    });
  } catch(e) {
    suggestionsEl.innerHTML = '<div class="sugg-item text-muted small">Erreur réseau — vérifiez votre connexion</div>';
  }
}

adresseInput.addEventListener('input', () => {
  clearTimeout(nominatimTimer);
  suggestionsEl.innerHTML = '';
  livResultEl.style.display = 'none';
  kmLivraison = 0; adresseLiv = adresseInput.value;
  renderCart();
  nominatimTimer = setTimeout(() => fetchSuggestions(adresseInput.value), 500);
});

// Cacher suggestions si clic ailleurs
document.addEventListener('click', e => {
  if (!adresseInput.contains(e.target) && !suggestionsEl.contains(e.target)) {
    suggestionsEl.innerHTML = '';
  }
});

// Init 
adresseLiv = adresseInput.value;

// Si une adresse est pré-remplie (depuis le profil), géocoder automatiquement
if (adresseInput.value.trim().length > 4) {
  (async () => {
    try {
      const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(adresseInput.value + ', France')}&format=json&limit=1&countrycodes=fr`;
      const results = await (await fetch(url, {headers:{'Accept-Language':'fr'}})).json();
      if (results.length) {
        const lat = parseFloat(results[0].lat);
        const lng = parseFloat(results[0].lon);
        kmLivraison = haversine(BDX_LAT, BDX_LNG, lat, lng);
        adresseLiv  = results[0].display_name;
        showLivResult(kmLivraison, adresseLiv);
        renderCart();
      }
    } catch(e) {}
  })();
}

renderCart();
updateNbPersonnesUI();
</script>
<!-- Bootstrap DOIT être chargé avant le script du modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!--  Modal détail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 pb-0" style="background:var(--dark);">
                <div>
                <p id="m-cat" class="mb-0" style="color:var(--gold);font-size:.72rem;letter-spacing:2px;text-transform:uppercase;"></p>
                <h4 id="m-nom" class="text-white fw-bold mb-0" style="font-family:'Playfair Display',serif;"></h4>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-5" id="m-img-wrap"></div>
                    <div class="col-md-7 p-4">
                        <div class="mb-3">
                            <div id="m-prix"></div>
                            <div id="m-pers" class="mt-1"></div>
                        </div>
                        <p id="m-desc" class="text-muted mb-4" style="line-height:1.75;"></p>
                        <div class="mb-3">
                            <p class="fw-bold small mb-1">🥗 Régime alimentaire</p>
                            <div id="m-regimes"></div>
                        </div>
                        <div class="mb-4">
                            <p class="fw-bold small mb-1">⚠️ Allergènes présents</p>
                            <div id="m-allergens"></div>
                        </div>
                        <div id="m-cta"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Labels allergènes & régimes
const AL = <?= json_encode([
  'gluten'         => ['e'=>'🌾','l'=>'Gluten'],
  'lactose'        => ['e'=>'🥛','l'=>'Lactose'],
  'oeufs'          => ['e'=>'🥚','l'=>'Oeufs'],
  'fruits_a_coque' => ['e'=>'🥜','l'=>'Fruits à coque'],
  'poisson'        => ['e'=>'🐟','l'=>'Poisson'],
  'crustaces'      => ['e'=>'🦐','l'=>'Crustacés'],
  'soja'           => ['e'=>'🫘','l'=>'Soja'],
  'arachides'      => ['e'=>'🥜','l'=>'Arachides'],
  'celeri'         => ['e'=>'🥬','l'=>'Céleri'],
  'moutarde'       => ['e'=>'🌿','l'=>'Moutarde'],
  'sesame'         => ['e'=>'🌱','l'=>'Sésame'],
  'sulfites'       => ['e'=>'🍷','l'=>'Sulfites'],
], JSON_UNESCAPED_UNICODE) ?>;

const RG = <?= json_encode([
  'vegetarien'   => ['e'=>'🥗','l'=>'Végétarien'],
  'vegan'        => ['e'=>'🌱','l'=>'Vegan'],
  'sans_gluten'  => ['e'=>'🚫','l'=>'Sans gluten'],
  'sans_lactose' => ['e'=>'🥛','l'=>'Sans lactose'],
], JSON_UNESCAPED_UNICODE) ?>;

const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

document.querySelectorAll('.btn-detail').forEach(btn => {
  btn.addEventListener('click', () => {
    const d = btn.dataset;

    document.getElementById('m-cat').textContent  = d.cat;
    document.getElementById('m-nom').textContent  = d.nom;
    document.getElementById('m-desc').textContent = d.desc;

    // Prix - par personne si applicable
    const prixEl = document.getElementById('m-prix');
    if (d.ppp) {
      prixEl.innerHTML = `
        <span style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">${d.ppp} €</span>
        <span style="font-size:.8rem;color:#888;display:block;">par personne &nbsp;•&nbsp; Total ${d.prix} €</span>`;
    } else {
      prixEl.innerHTML = `<span style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">${d.prix} €</span>`;
    }

    // Badge minimum personnes
    const pers = parseInt(d.pers) || 1;
    document.getElementById('m-pers').innerHTML = pers > 1
      ? `<span style="display:inline-flex;align-items:center;gap:.3rem;background:linear-gradient(90deg,${d.color},${d.color}cc);color:#fff;border-radius:50px;padding:3px 10px;font-size:.75rem;font-weight:700;">👥 À partir de ${pers} personnes</span>`
      : '';

    // Image
    const iw = document.getElementById('m-img-wrap');
    iw.innerHTML = d.img
      ? `<img src="${d.img}" alt="${d.nom}" style="width:100%;height:100%;min-height:300px;object-fit:cover;border-radius:0;">`
      : `<div style="height:100%;min-height:300px;background:${d.color};display:flex;align-items:center;justify-content:center;font-size:5rem;border-radius:0;">${d.emoji}</div>`;

    // Régimes
    const rgs = d.regimes.split(',').map(s => s.trim()).filter(Boolean);
    document.getElementById('m-regimes').innerHTML = rgs.length
      ? rgs.map(r => { const x = RG[r]||{e:'',l:r}; return `<span style="display:inline-flex;align-items:center;gap:.2rem;background:#E8F5E9;border:1px solid #A5D6A7;color:#1B5E20;border-radius:6px;padding:3px 9px;font-size:.78rem;font-weight:600;margin:2px;">${x.e} ${x.l}</span>`; }).join('')
      : '<span class="text-muted small">Aucune mention spécifique</span>';

    // Allergènes
    const als = d.allergens.split(',').map(s => s.trim()).filter(Boolean);
    document.getElementById('m-allergens').innerHTML = als.length
      ? als.map(a => { const x = AL[a]||{e:'⚠️',l:a}; return `<span style="display:inline-flex;align-items:center;gap:.2rem;background:#FFF3E0;border:1px solid #FFCC80;color:#BF360C;border-radius:6px;padding:3px 9px;font-size:.78rem;font-weight:600;margin:2px;">${x.e} ${x.l}</span>`; }).join('')
      : '<span style="display:inline-flex;align-items:center;gap:.2rem;background:#E8F5E9;border:1px solid #A5D6A7;color:#1B5E20;border-radius:6px;padding:3px 9px;font-size:.78rem;font-weight:600;margin:2px;">✅ Aucun allergène majeur déclaré</span>';

    // CTA - bouton ajouter au panier depuis le modal
    document.getElementById('m-cta').innerHTML = `
      <button class="btn w-100 fw-bold text-white"
              style="background:${d.color};"
              data-bs-dismiss="modal"
              onclick="cartAction({action:'add',menu_id:'${btn.dataset.id ?? ''}'})">
        <i class="bi bi-cart-plus me-2"></i>Ajouter au panier
      </button>`;

    detailModal.show();
  });
});
</script>
    
</body>
</html>