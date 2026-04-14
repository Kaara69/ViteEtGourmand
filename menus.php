<?php
session_start();

include 'includes/db.php';


$active_page = 'menus';

// true si l'utilisateur est connecté, false sinon
$is_logged = isset($_SESSION['user_id']);


// TABLEAUX DE CONFIGURATION
// Clé = identifiant en base · Valeur = [emoji, label affiché]


$allergen_labels = [
    'gluten'         => ['🌾', 'Gluten'],
    'lactose'        => ['🥛', 'Lactose'],
    'oeufs'          => ['🥚', 'Oeufs'],
    'fruits_a_coque' => ['🥜', 'Fruits à coque'],
    'poisson'        => ['🐟', 'Poisson'],
    'crustaces'      => ['🦐', 'Crustacés'],
    'soja'           => ['🫘', 'Soja'],
    'arachides'      => ['🥜', 'Arachides'],
    'celeri'         => ['🥬', 'Céleri'],
    'moutarde'       => ['🌿', 'Moutarde'],
    'sesame'         => ['🌱', 'Sésame'],
    'sulfites'       => ['🍷', 'Sulfites'],
];

$regime_labels = [
    'vegetarien'   => ['🥗', 'Végétarien'],
    'vegan'        => ['🌱', 'Vegan'],
    'sans_gluten'  => ['🚫', 'Sans gluten'],
    'sans_lactose' => ['🥛', 'Sans lactose'],
];

// Couleur et emoji par cat (pour les cartes et le placeholder image)
$cat_config = [
    'Formules'   => ['emoji' => '🍱', 'couleur' => '#2D4A3E'],
    'Plateaux'   => ['emoji' => '🧀', 'couleur' => '#5C3D1E'],
    'Plats'      => ['emoji' => '🍖', 'couleur' => '#1C1510'],
    'Vegetarien' => ['emoji' => '🥗', 'couleur' => '#1E4D2B'],
    'Desserts'   => ['emoji' => '🍮', 'couleur' => '#4A1942'],
    'Boissons'   => ['emoji' => '🥤', 'couleur' => '#1A3A5C'],
];


//  REQUETE BASE DE DONNEES

// Récupère le nombre maximum de personnes (slider)
// try/catch : si la colonne n'existe pas, on met 1 par défaut
try {
    $max_pers = (int) $pdo
        ->query("SELECT MAX(personnes_min) FROM menus WHERE disponible = 1")
        ->fetchColumn();
} catch (Exception $e) {
    $max_pers = 1;
}
if ($max_pers < 1) $max_pers = 1;

// Récupère tous les menus disponibles, triés par catégorie puis par nom
$menus = $pdo
    ->query("SELECT * FROM menus WHERE disponible = 1 ORDER BY categorie, nom")
    ->fetchAll();

// Regroupe les menus par cat dans un tableau associatif
// Résultat : ['Plats' => [...], 'Desserts' => [...], ...]
$menus_par_categorie = [];
foreach ($menus as $menu) {
    $menus_par_categorie[$menu['categorie']][] = $menu;
}

// Liste des noms de catégories (utilisée pour les filtres)
$categories = array_keys($menus_par_categorie);
?>

