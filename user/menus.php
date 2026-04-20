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

    
</body>
</html>