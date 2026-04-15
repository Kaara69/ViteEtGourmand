<?php
session_start();

include 'include/db.php';


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
            <div class="fbar-inner align-items-center">
                <span class="fbar-label text-uppercase">Filtrer :</span>

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
                <div class="fbar-right align-items-center">
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

                            <!-- contenu principal -->
    <div class="container py-5">
                            <!-- bandeau "connectez vous si l'user n'est oas connecté -->
        <?php if ($is_logged): ?>
        <div class="alert mb-5 d-flex align-items-center gap-3 flex-wrap" 
            style="background: linear-gradient(90deg, #1C1510, #2D1F14); border: none; border-radius: 12px;">
            <span style="font-size: 1.5rem;">🔒</span>
            <div>
                <strong dtyle="color: var(--gold);">Connectez-vous pour commander</strong>
                <div class="small" style="color: rgba(255,255,255,.7);">
                    Créez un compte gratuitement pour passer vos commandes en ligne.</div>
            </div>
        
            <div class="ms-auto d-flex gap-2">
                <a href="login.php" class="btn btn-sm btn-outline-light">Connexion</a>
                <a href="register.php" class="btn btn-sm btn-gold">S'inscrire</a>
            </div>
        </div>
        <?php endif; ?>

                            <!-- message affiché qd aucune card ne correspond aux filtres -->
                             <!-- js affiche si besoin display none -->
        <div class="text-center py-5" id="no-results" style="display: none;">
            <p style="font-size: 3rem;">🔍</p>
            <h5 class="text-muted">Aucun plat ne correspond à vos filtres</h5>
            <p class="text-muted small">Modifiez oiu réinitialisez vos critères de recherche</p>
            <button class="pill" id="btn-reset2">
                <i class="bi bi-x-circle me-1"></i>Réinitialiser
            </button>
        </div>

                            <!-- grille des cards : une par menu -->
        <div class="row g-4" id="menu-grid">

            <?php foreach ($menus as $menu):

            // Récupère la config visuelle de la catégorie (emoji + couleur)
            $cat    = $menu['categorie'];
            $config = $cat_config[$cat] ?? ['emoji' => '🍽️', 'couleur' => '#1C1510'];

            // Transforme la chaîne "gluten,lactose" en tableau ['gluten','lactose']
            // array_filter supprime les valeurs vides
            $allergenes = array_filter(array_map('trim', explode(',', $menu['allergenes'] ?? '')));
            $regimes    = array_filter(array_map('trim', explode(',', $menu['regime']    ?? '')));

            // Minimum de personnes requis (1 si non renseigné)
            $nb_pers = max(1, (int)($menu['personnes_min'] ?? 1));

            ?>
            <!--
                Les attributs data-* stockent les infos filtrables.
                JS les lit pour décider si la carte doit être visible ou cachée.
            -->
            <div class="col-md-6 col-lg-4 menu-item-wrap"
                data-cat="<?= htmlspecialchars($cat) ?>"
                data-allergens="<?= htmlspecialchars($menu['allergenes'] ?? '') ?>"
                data-regimes="<?= htmlspecialchars($menu['regime'] ?? '') ?>"
                data-pers="<?= $nb_pers ?>">

                <div class="menu-card-pub">

                    <!-- Partie image -->
                    <div class="position-relative">

                        <?php if (!empty($menu['image_url'])): ?>
                            <!-- Image réelle depuis la base de données -->
                            <img src="<?= htmlspecialchars($menu['image_url']) ?>"
                                alt="<?= htmlspecialchars($menu['nom']) ?>"
                                class="menu-img" loading="lazy">
                        <?php else: ?>
                            <!-- Placeholder coloré avec emoji si pas d'image -->
                            <div class="menu-img-ph" style="background: <?= $config['couleur'] ?>;">
                                <?= $config['emoji'] ?>
                            </div>
                        <?php endif; ?>

                        <!-- Badge catégorie (ex: "Plats") -->
                        <span class="cat-badge"><?= htmlspecialchars($cat) ?></span>

                        <!-- Badge "X+ pers." visible seulement si besoin de plusieurs personnes -->
                        <?php if ($nb_pers > 1): ?>
                            <span class="pers-badge">👥 <?= $nb_pers ?>+ pers.</span>
                        <?php endif; ?>

                        <!-- Badges régime alimentaire en bas à droite de l'image -->
                        <div style="position: absolute; bottom: 8px; right: 8px; display: flex; flex-wrap: wrap; gap: 3px; justify-content: flex-end;">
                            <?php if (in_array('vegan', $regimes)): ?>
                                <span class="rg-badge" style="background: #1E4D2B; color: #fff;">🌱 Vegan</span>
                            <?php elseif (in_array('vegetarien', $regimes)): ?>
                                <span class="rg-badge" style="background: #2E7D47; color: #fff;">🥗 Végé</span>
                            <?php endif; ?>
                            <?php if (in_array('sans_gluten', $regimes)): ?>
                                <span class="rg-badge" style="background: #1A3A5C; color: #fff;">🚫 Gluten</span>
                            <?php endif; ?>
                            <?php if (in_array('sans_lactose', $regimes)): ?>
                                <span class="rg-badge" style="background: #4A1942; color: #fff;">🚫 Lactose</span>
                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- Partie texte de la carte -->
                    <div class="card-body p-4 d-flex flex-column flex-grow-1">

                        <!-- Ligne nom + prix -->
                        <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                            <h5 class="fw-bold mb-0" style="line-height: 1.3; font-size: 1rem;">
                                <?= htmlspecialchars($menu['nom']) ?>
                            </h5>
                            <div class="text-end flex-shrink-0">
                                <?php if ($nb_pers > 1):
                                    // Prix par personne = prix total ÷ nb de personnes
                                    $prix_par_pers = $menu['prix'] / $nb_pers;
                                ?>
                                    <div class="prix">
                                        <?= number_format($prix_par_pers, 2, ',', ' ') ?> €
                                        <span style="font-size: .65rem; font-weight: 400; color: #888;">/pers.</span>
                                    </div>
                                    <div style="font-size: .7rem; color: #aaa;">
                                        Total : <?= number_format($menu['prix'], 2, ',', ' ') ?> €
                                    </div>
                                <?php else: ?>
                                    <div class="prix"><?= number_format($menu['prix'], 2, ',', ' ') ?> €</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Badge "À partir de X personnes" -->
                        <?php if ($nb_pers > 1): ?>
                        <div class="mb-2">
                            <span style="display: inline-flex; align-items: center; gap: .3rem; background: <?= $config['couleur'] ?>; color: #fff; border-radius: 50px; padding: 3px 10px; font-size: .72rem; font-weight: 700;">
                                👥 À partir de <?= $nb_pers ?> personnes
                            </span>
                        </div>
                        <?php endif; ?>

                        <!-- Description limitée à 2 lignes via CSS (-webkit-line-clamp) -->
                        <p class="text-muted small mb-2 flex-grow-1"
                        style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.55; min-height: 2.6em;">
                            <?= htmlspecialchars($menu['description']) ?>
                        </p>

                        <!-- Résumé allergènes (détail dans le modal) -->
                        <?php if (!empty($allergenes)): ?>
                            <div class="mb-3">
                                <span class="al-chip"
                                    style="background: #FFF3CD; color: #856404; font-size: .72rem; cursor: pointer;"
                                    onclick="this.closest('.menu-card-pub').querySelector('.btn-detail').click()">
                                    ⚠️ <?= count($allergenes) ?> allergène<?= count($allergenes) > 1 ? 's' : '' ?> — voir détails
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <span class="al-chip" style="background: #E8F5E9; color: #1B5E20; font-size: .72rem;">
                                    ✅ Sans allergènes déclarés
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Boutons d'action -->
                        <div class="d-flex gap-2">

                            <!--
                                Bouton "Détails" : ouvre le modal.
                                Toutes les infos du plat sont stockées dans les attributs data-*
                                pour être lues par le JavaScript ci-dessous.
                            -->
                            <button class="btn btn-outline-dark flex-grow-1 btn-sm btn-detail"
                                    data-nom="<?= htmlspecialchars($menu['nom'],        ENT_QUOTES) ?>"
                                    data-prix="<?= number_format($menu['prix'], 2, ',', ' ') ?>"
                                    data-ppp="<?= $nb_pers > 1 ? number_format($menu['prix'] / $nb_pers, 2, ',', ' ') : '' ?>"
                                    data-cat="<?= htmlspecialchars($cat,                ENT_QUOTES) ?>"
                                    data-desc="<?= htmlspecialchars($menu['description'], ENT_QUOTES) ?>"
                                    data-img="<?= htmlspecialchars($menu['image_url'] ?? '', ENT_QUOTES) ?>"
                                    data-allergens="<?= htmlspecialchars($menu['allergenes'] ?? '', ENT_QUOTES) ?>"
                                    data-regimes="<?= htmlspecialchars($menu['regime'] ?? '',    ENT_QUOTES) ?>"
                                    data-pers="<?= $nb_pers ?>"
                                    data-color="<?= $config['couleur'] ?>"
                                    data-emoji="<?= $config['emoji'] ?>">
                                <i class="bi bi-eye me-1"></i>Détails
                            </button>

                            <!-- Bouton "Commander" ou "Connexion" selon l'état de session -->
                            <?php if ($is_logged): ?>
                                <a href="user/menu.php" class="btn btn-gold flex-grow-1 btn-sm">
                                    <i class="bi bi-cart-plus me-1"></i>Commander
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn flex-grow-1 btn-sm fw-semibold"
                                style="border: 2px solid var(--dark); color: var(--dark);">
                                    <i class="bi bi-lock me-1"></i>Connexion
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>>
            </div>
                <?php endforeach; ?>
        </div>
    </div>
                            <!-- modal detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
                            <!-- en tête modal -->
                <div class="modal-header border-0 pb-0">
                    <div>
                        <p id="m-cat" class="mb-0" style="color: var(--gold); font-size: .72rem; letter-spacing: 2px; text-transform: uppercase;"></p>
                        <h4 id="m-nom" class="text-white fw-bold mb-0" syle="font-family: 'Playfair Display', serif;"></h4>
                    </div>
                    <button class="btn-close btn-close-white ms-auto" type="button" data-bs-dismiss="modal"></button>
                </div> <!-- modal header-->
                                <!-- corps modal  -->
                
                <div class="modal-body p-0">
                    <div class="row g-0">
                                <!-- img injecté par js-->
                        <div class="col-md-5" id="m-img-wrap"></div> 
                                <!-- infos injecté par js-->
                        <div class="col-md-7 p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <span id="m-prix"></span>
                                <div id="m-pers"></div>
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
                    </div> <!-- row -->
                </div> <!-- modal body -->          
            </div> <!-- modal content -->
        </div> <!-- modal-dialog -->
    </div> <!--modal fade-->

<?php include 'include/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- passer php en json pour les utiliser en js ici! -->
</body>
