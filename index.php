<?php
// TEMPORAIRE : pour voir le site sans erreurs
// session_start();
// $is_logged = false;
// $role = '';
// $avis = []; // Pas d'avis pour l'instant
// $horaires = [];

// function stars($note) {
//     $html = '';
//     for($i=1; $i<=5; $i++) {
//         $html .= ($i <= $note) ? '★' : '☆';
//     }
//     return $html;
// }



// Démarre la session PHP (pour accéder à $_SESSION) session_start();

// Connexions et includes
include 'includes/db.php';
include 'includes/auth.php';


// Vérification de connexion + rôle
$is_logged = isset($_SESSION['user_id']);
$role      = '';
if ($is_logged && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
}

// Chargement de la navbar adaptée
if ($is_logged) {
    if ($role === 'admin') {
        include 'includes/partials/admin_nav.php';
    } elseif ($role === 'employee') {
        include 'includes/partials/employee_nav.php';
    } else {
        // Par défaut : simple utilisateur connecté
        include 'includes/partials/user_nav.php';
    }
} else {
    include 'includes/partials/public_nav.php';
}

// Jour de la semaine en français
$jour_fr = [
    'Sunday'    => 'Dimanche',
    'Monday'    => 'Lundi',
    'Tuesday'   => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday'  => 'Jeudi',
    'Friday'    => 'Vendredi',
    'Saturday'  => 'Samedi'
];
$today_fr = $jour_fr[date('l')];

// Récupération des 6 derniers avis approuvés
$avis = [];
$sql_avis = "SELECT * FROM avis WHERE statut='approuvé' ORDER BY created_at DESC LIMIT 6";
$stmt_avis = $pdo->query($sql_avis);
if ($stmt_avis) {
    $avis = $stmt_avis->fetchAll();
}

// Récupération des horaires
$horaires = [];
$sql_horaires = "SELECT * FROM horaires ORDER BY id";
$stmt_horaires = $pdo->query($sql_horaires);
if ($stmt_horaires) {
    while ($h = $stmt_horaires->fetch()) {
        $horaires[$h['jour']] = $h;
    }
}


// function stars($note) {
//     $note = (int) $note;
//     $html = '';
//     for ($i = 0; $i < 5; $i++) {
//         if ($i < $note) {
//             $html .= '<span class="star">★</span>';
//         } else {
//             $html .= '<span class="star">☆</span>';
//         }
//     }
//     return $html;
// }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Vite &amp; Gourmand – Cuisine d'exception</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/public.css">
</head>

<body>

                            <!-- NAVBAR PHP -->


                            <!-- HERO -->
<section class="hero" id="hero">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="hero-badge">Traiteur professionnel depuis 2001</div>
                <h1>La gastronomie <em>artisanale</em> livrée chez vous</h1>
                <div class="hero-line"></div>
                <p class="mb-4">Des plats élaborés avec passion, des produits sélectionnés auprès des meilleurs producteurs locaux. Pour vos événements d'entreprise, repas familiaux et occasions spéciales.</p>
                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="menus.php" class="btn btn-gold">Découvrir la carte</a>
                    <a href="contact.php" class="btn btn-outline-gold">Nous contacter</a>
                </div>
                <div class="d-flex gap-4 flex-wrap">
                    <div class="hero-stat">
                        <div class="number">25</div>
                        <div class="label">ans d'expérience</div>
                    </div>
                    <div class="hero-stat">
                        <div class="number">98%</div>
                        <div class="label">clients satisfaits</div>
                    </div>
                    <div class="hero-stat">
                        <div class="number">100%</div>
                        <div class="label">produits frais</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <div style="width: 360px;height: 360px;margin: auto;border-radius: 50%;background: linear-gradient(135deg, rgba(201,151,61,.15), rgba(201,151,61,.05));
                    border: 2px solid var(--gold);display: flex;align-items: center;justify-content: center;">
                    <img src="uploads/chefs.jpg" alt="Julie et José" style="width: 320px;height: 320px;object-fit: cover; border-radius:50%;">
                </div>
            </div>
        </div>
    </div>
</section>

                            <!-- ENGAGEMENT  -->
<section class="py-6" style="background:#fff;padding:5rem 0;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">Pourquoi nous choisir</div>
            <h2 class="display-font mt-2">Notre engagement qualité</h2>
            <p class="text-muted mx-auto" style="max-width:520px;">Chaque prestation est pensée dans les moindres détails pour vous offrir une expérience culinaire mémorable.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-sm-6 d-flex">
                <div class="feature-card text-center">
                    <div class="feature-icon mx-auto"><i class="bi bi-award-fill"></i></div>
                    <h5 class="fw-bold">Qualité premium</h5>
                    <p class="text-muted small mb-0">Produits frais, circuits courts et fournisseurs locaux sélectionnés avec soin.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 d-flex">
                <div class="feature-card text-center">
                <div class="feature-icon mx-auto"><i class="bi bi-clock-fill"></i></div>
                <h5 class="fw-bold">Ponctualité</h5>
                <p class="text-muted small mb-0">Livraisons dans les délais convenus, toujours. Votre temps est précieux.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 d-flex">
                <div class="feature-card text-center">
                    <div class="feature-icon mx-auto"><i class="bi bi-heart-fill"></i></div>
                    <h5 class="fw-bold">Fait maison</h5>
                    <p class="text-muted small mb-0">Recettes artisanales élaborées quotidiennement par nos chefs cuisiniers.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 d-flex">
                <div class="feature-card text-center">
                <div class="feature-icon mx-auto"><i class="bi bi-headset"></i></div>
                <h5 class="fw-bold">Service dédié</h5>
                <p class="text-muted small mb-0">Une équipe disponible pour personnaliser chaque commande selon vos besoins.</p>
                </div>
            </div>
        </div>
    </div>
</section>


                            <!-- A PROPOS -->
<section style="background:var(--dark);padding:6rem 0;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="section-label" style="color:var(--gold);">À propos de nous</div>
                <h2 class="display-font text-white mt-2">Une passion transmise <em style="color:var(--gold);font-style:italic;">depuis 2001</em></h2>
                <div style="width:60px;height:3px;background:var(--gold);margin:1.5rem 0;"></div>
                <p style="color:rgba(255,255,255,.75);font-size:1.05rem;line-height:1.8;">
                Fondé par Julie & José , Vite &amp; Gourmand est né d'une conviction simple : la gastronomie artisanale mérite d'être accessible à tous. Depuis plus de 25 ans, notre équipe de chefs passionnés élabore chaque jour des recettes authentiques avec des produits soigneusement sélectionnés auprès de producteurs locaux.
                </p>
                <p style="color:rgba(255,255,255,.65);line-height:1.8;">
                Que ce soit pour un déjeuner d'entreprise, un événement familial ou une occasion spéciale, nous mettons tout notre savoir-faire au service de votre satisfaction. Chaque plat est une promesse : celle de vous offrir le meilleur de la cuisine française, avec chaleur et générosité.
                </p>
                <div class="row g-3 mt-4">
                    <div class="col-6">
                        <div style="border-left:3px solid var(--gold);padding-left:1rem;">
                        <div style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">25+</div>
                        <div style="color:rgba(255,255,255,.6);font-size:.9rem;">ans d'expérience</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="border-left:3px solid var(--gold);padding-left:1rem;">
                        <div style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">98%</div>
                        <div style="color:rgba(255,255,255,.6);font-size:.9rem;">clients statisfaits</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="border-left:3px solid var(--gold);padding-left:1rem;">
                        <div style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">100%</div>
                        <div style="color:rgba(255,255,255,.6);font-size:.9rem;">produits frais</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="border-left:3px solid var(--gold);padding-left:1rem;">
                        <div style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--gold);font-weight:700;">3</div>
                        <div style="color:rgba(255,255,255,.6);font-size:.9rem;">chefs cuisiniers</div>
                        </div>
                    </div>
                </div>
                <div class="mt-5">
                    <a href="contact.php" class="btn btn-gold me-3 mb-4">Nous contacter</a>
                    <a href="menus.php" class="btn btn-outline-gold mb-4">Voir la carte</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                <div class="col-12">
                    <div style="background:rgba(255,255,255,.05);border:1px solid rgba(201,151,61,.2);border-radius:14px;padding:1.75rem;display:flex;gap:1rem;align-items:flex-start;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(201,151,61,.15);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">👨‍🍳</div>
                    <div>
                        <h6 class="fw-bold text-white mb-1">Notre équipe</h6>
                        <p style="color:rgba(255,255,255,.6);font-size:.9rem;margin:0;">Trois chefs diplômés, passionnés et complémentaires. Chacun apporte son expertise — pâtisserie, plats chauds, plateaux froids — pour un résultat d'exception.</p>
                    </div>
                    </div>
                </div>
                <div class="col-12">
                    <div style="background:rgba(255,255,255,.05);border:1px solid rgba(201,151,61,.2);border-radius:14px;padding:1.75rem;display:flex;gap:1rem;align-items:flex-start;">
                    <div style="width:48px;height:48px;border-radius:12px;background:rgba(201,151,61,.15);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">🌿</div>
                    <div>
                        <h6 class="fw-bold text-white mb-1">Nos engagements</h6>
                        <p style="color:rgba(255,255,255,.6);font-size:.9rem;margin:0;">Produits de saison, circuits courts, fournisseurs locaux. Nous travaillons avec des artisans qui partagent nos valeurs de qualité et de durabilité.</p>
                    </div>
                    </div>
                </div>
                <div class="col-12">
                    <div style="background:rgba(255,255,255,.05);border:1px solid rgba(201,151,61,.2);border-radius:14px;padding:1.75rem;display:flex;gap:1rem;align-items:flex-start;">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(201,151,61,.15);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">🏆</div>
                            <div>
                                <h6 class="fw-bold text-white mb-1">Reconnus et primés</h6>
                                <p style="color:rgba(255,255,255,.6);font-size:.9rem;margin:0;">Meilleur ouvrier de France en 2019 et 2022. Notre engagement pour la qualité est reconnu par nos pairs et nos clients.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

                            <!--  AVIS CLIENT -->

<section class="py-5" id="avis" style="background:var(--cream);padding:5rem 0!important;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">Ils nous font confiance</div>
            <h2 class="display-font mt-2">Avis de nos clients</h2>
        </div>
        <?php if ($avis): ?>
        <div class="row g-4">
            <?php foreach ($avis as $a): ?>
            <div class="col-md-6 col-lg-4">
                <div class="avis-card">
                    <div class="d-flex align-items-start mb-3">
                        <div class="avis-quote me-2">"</div>
                        <div>
                            <div class="stars"><?= stars($a['note']) ?></div>
                            <div class="fw-bold mt-1"><?= htmlspecialchars($a['nom']) ?></div>
                            <div class="text-muted small"><?= date('M Y', strtotime($a['created_at'])) ?></div>
                        </div>
                    </div>
                    <p class="text-muted mb-0 fst-italic small"><?= htmlspecialchars($a['contenu']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-muted">Aucun avis pour le moment.</p>
        <?php endif; ?>
        <?php if ($is_logged && $role === 'client'): ?>
        <div class="text-center mt-4">
            <a href="user/profile.php#avis" class="btn btn-outline-dark">Laisser un avis</a>
        </div>
        <?php elseif (!$is_logged): ?>
        <div class="text-center mt-4">
            <a href="register.php" class="btn btn-outline-dark">Rejoindre et laisser un avis</a>
        </div>
        <?php endif; ?>
    </div>
</section>

                            <!-- HORAIRES -->
                            <!-- FOOTER -->
<?php include 'includes/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
</body>
</html>