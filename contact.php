<?php   
session_start();
include 'includes/db.php';

$active_page = 'contact';

$msg    = '';
$error  = '';

// On verifie si le formulaire a été envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // on recupere les champs du formulaire et sécurise avec "?? '' "
    $nom        = trim($_POST['nom']        ?? '');
    $email      = trim($_POST['email']      ?? '');
    $message    = trim($_POST['message']    ?? '');

    // on vérifie que les champs obligatoires ne sont pas vides
    if (empty($nom) || empty($email) || empty($message)) {
        $error = 'Tous les champs marqués * sont obligatoires.';
    }
    // Si tous les champs sont remplis, on considère que le message est envoyé
    else {
        $msg = 'Votre message a été envoyé ! Nous vous répondrons dans les plus brefs délais.';
    }
}
// on charge les horaires (table horaires)
$horaire = [];
$horaires_rows = $pdo->query("SELECT * FROM horaires ORDER BY id")-> fetchAll();
foreach($horaires_rows as $h) {
    $horaires[$h['jour']] = $h;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact – Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/public.css">
</head>
<body>
    <?php include 'includes/partials/public_nav.php'; ?>

    <div class="page-hero">
        <div class="container">
            <p style="color:rgba(201,151,61,.8);font-size:.75rem;letter-spacing:3px;text-transform:uppercase;margin-bottom:.5rem;">Nous joindre</p>
            <h1 style="font-family:'Playfair Display',serif;color:#fff;">Contactez-nous</h1>
            <p style="color:rgba(255,255,255,.6);max-width:450px;margin:.5rem auto 0;">
                Une question, un devis, une demande spéciale ? Notre équipe vous répond rapidement. 
            </p>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-4">
                <h4 class="fw-bold mb-4" style="font-family:'Playfair Display',serif;">Nos informations</h4>
                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <h6 class="fw-bold">Adresse</h6>
                        <p class="text-muted small mb-0">7 rue des Saveurs, 33000 Bordeaux</p>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-telephone-fill"></i></div>
                        <h6 class="fw-bold">Téléphone</h6>
                        <p class="mb-0"><a href="tel:0123456789" style="color:var(--gold);text-decoration:none;">05 12 34 56 78</a></p>
                    </div>
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-envelope-fill"></i></div>
                        <h6 class="fw-bold">Email</h6>
                        <p class="mb-0"><a href="mailto:contact@vitegourmand.fr" style="color:var(--gold);text-decoration:none;font-size:.9rem;">contact@vitegourmand.fr</a></p>
                    </div>
                </div> <!-- dflex -->
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-clock-fill"></i></div>
                    <h6 class="fw-bold mb-3">Horaires d'ouverture</h6>
                    <?php foreach($horaires as $jour=>$h): ?>
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        <span class="fw-semibold"><?= $jour ?></span>
                        <?php if($h['ferme']): ?><span class="text-danger">Fermé</span>
                        <?php else: ?><span class="text-muted"><?= $h['heure_ouverture'] ?> – <?= $h['heure_fermeture'] ?></span><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div> <!-- col -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                    <h4 class="fw-bold mb-1" style="font-family:'Playfair Display',serif;">Envoyez-nous un message</h4>
                    <p class="text-muted mb-4">Nous répondons généralement sous 24h en jours ouvrés.</p>
                    <?php if($msg): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $msg ?></div><?php endif; ?>
                    <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nom complet *</label>
                                <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($_POST['nom']??(isset($_SESSION['nom'])?$_SESSION['nom']:'')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email *</label>
                                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email']??(isset($_SESSION['email'])?$_SESSION['email']:'')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Sujet</label>
                                <select name="sujet" class="form-select">
                                    <option>Demande de devis</option>
                                    <option>Question sur les menus</option>
                                    <option>Commande spéciale / événement</option>
                                    <option>Réclamation</option>
                                    <option>Autre</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Message *</label>
                                <textarea name="message" class="form-control" rows="6" required placeholder="Décrivez votre demande..."><?= htmlspecialchars($_POST['message']??'') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-gold btn-lg px-5">
                                    <i class="bi bi-send me-2"></i>Envoyer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- row -->
    </div> <!-- container -->
    <?php include 'includes/partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>