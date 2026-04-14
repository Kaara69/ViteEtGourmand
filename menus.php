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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nos menus – Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/public.css">
</head>

<body>
    <!-- include public nav -->

                            <!-- hero -->
    <div class="page-hero">
        <div class="container">
            <p style="color: rgba(201,151,61,.8); font-size: .75rem; letter-spacing: 3px; text-transform: uppercase; margin-bottom: .5rem;">
            Notre carte
            </p>
            <h1 style="font-family: 'Playfair Display', serif; color: #fff; font-size: clamp(2rem, 4vw, 3.2rem);">
            Des saveurs qui <em style="color: var(--gold);">racontent</em> une histoire
            </h1>
            <p style="color: rgba(255,255,255,.6); max-width: 500px; margin: .75rem auto 0;">
            Filtrez par catégorie, nombre de personnes, régime alimentaire ou allergènes.
            </p>
        </div>
    </div>

                            <!-- barre de filtres (dropdown html, JS qui filtre les cartes coté clients) -->
    <div class="filter-bar">
        <div class="container">
            <div class="fbar-inner">
                <span class="fbar-label">Filtrer :</span>

                            <!-- Dropdown : Catégorie -->
                <div class="fbar-drop" id="drop-cat">
                    <button class="fbar-btn" id="btn-cat" onclick="toggleDrop('cat')">
                        <span class="fbar-btn-text" id="label-cat">Catégorie</span>
                        <svg class="fbar-chevron" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="fbar-panel" id="panel-cat">

                            <!-- Option "toutes les catégories" sélectionnée par défaut -->
                        <label class="fbar-option fbar-radio">
                            <input type="radio" name="cat" value="all" checked>
                            <span class="fbar-option-icon">🍽️</span> Toutes catégories
                        </label>

                            <!-- Génère une option par catégorie trouvée en base -->
                        <?php foreach ($categories as $cat):
                            $emoji = $cat_config[$cat]['emoji'] ?? '🍽️';
                        ?>
                        <label class="fbar-option fbar-radio">
                            <input type="radio" name="cat" value="<?= htmlspecialchars($cat) ?>">
                            <span class="fbar-option-icon"><?= $emoji ?></span>
                            <?= htmlspecialchars($cat) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                        <!-- ----- Dropdown : Nombre de personnes (slider) ----- -->
                        <!-- Affiché seulement si au moins un menu a personnes_min > 1 -->
                <?php if ($max_pers > 1): ?>
                <div class="fbar-drop" id="drop-pers">
                    <button class="fbar-btn" id="btn-pers" onclick="toggleDrop('pers')">
                        <span class="fbar-btn-text" id="label-pers">👥 Personnes</span>
                        <svg class="fbar-chevron" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="fbar-panel" id="panel-pers" style="min-width: 220px;">
                        <div style="padding: .25rem .25rem 0">
                            <div class="d-flex justify-content-between mb-2">
                                <span style="font-size: .8rem; color: #888">Minimum :</span>
                            <!-- Ce span est mis à jour par JS quand on bouge le slider -->
                                <span id="pers-display" style="font-weight: 700; color: var(--gold); font-size: .9rem;">1+ pers.</span>
                            </div>
                            <input type="range" class="pers-slider" id="pers-slider"
                               min="1" max="<?= $max_pers ?>" value="1" step="1">
                            <div class="d-flex justify-content-between mt-1" style="font-size: .7rem; color: #bbb">
                                <span>1</span>
                                <span><?= $max_pers ?>+</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                            <!-- Dropdown : Régime alimentaire (cases à cocher)-->
                <div class="fbar-drop" id="drop-regime">
                    <button class="fbar-btn" id="btn-regime" onclick="toggleDrop('regime')">
                        <span class="fbar-btn-text" id="label-regime">Régime</span>
                        <svg class="fbar-chevron" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="fbar-panel" id="panel-regime">
                        <?php foreach ($regime_labels as $cle => $infos): ?>
                        <label class="fbar-option">
                            <!-- data-filter et data-value sont lus par JS pour savoir quoi filtrer -->
                            <input type="checkbox" data-filter="regime" data-value="<?= $cle ?>">
                            <span class="fbar-option-icon"><?= $infos[0] ?></span>
                            <?= $infos[1] ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                            <!-- Dropdown : Allergènes (cases à cocher) -->
                <div class="fbar-drop" id="drop-allergen">
                    <button class="fbar-btn" id="btn-allergen" onclick="toggleDrop('allergen')">
                        <span class="fbar-btn-text" id="label-allergen">Allergènes</span>
                        <svg class="fbar-chevron" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="fbar-panel fbar-panel-wide" id="panel-allergen">
                        <p class="fbar-panel-hint">Exclure les menus contenant :</p>
                        <div class="fbar-grid">
                            <?php foreach ($allergen_labels as $cle => $infos): ?>
                            <label class="fbar-option">
                                <input type="checkbox" data-filter="allergen" data-value="<?= $cle ?>">
                                <span class="fbar-option-icon"><?= $infos[0] ?></span>
                                <?= $infos[1] ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                            <!--Compteur de résultats + bouton reset -->
                <div class="fbar-right">
                    <span id="results-count" class="fbar-count"></span>
                    <button class="fbar-reset" id="btn-reset">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Réinitialiser
                    </button>
                </div>

            </div>
        </div>
    </div>
</body>
