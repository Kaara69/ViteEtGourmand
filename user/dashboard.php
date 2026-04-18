<?php
session_start();

// Inclure le fichier d'authentification (vérifie si utilisateur connecté)
include '../include/auth.php';
checkLogin('../login.php'); // si pas connecté, redirige vers la page de login

include '../include/db.php';

$active_page = 'dashboard';


$jours_fr = [
    'Sunday'    => 'Dimanche',
    'Monday'    => 'Lundi',
    'Tuesday'   => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday'  => 'Jeudi',
    'Friday'    => 'Vendredi',
    'Saturday'  => 'Samedi',
];

// 1. Récupérer le jour actuel en anglais
$jour_anglais = date('l');

// 2. Convertir ce jour en français
$auj = $jours_fr[$jour_anglais];


// 3. Charger l'horaire du jour dans la base (table horaires)
$stmt = $pdo->prepare("SELECT * FROM horaires WHERE jour = ?");
$stmt->execute([$auj]);
$horaire = $stmt->fetch();


// 4. Charger les 5 dernières commandes de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$mes_cmd = $stmt->fetchAll();


// 5. Compter le nombre total de commandes de l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_cmd = $stmt->fetchColumn(); // renvoie un nombre entier
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace – Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/espace.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../include/partials/user_nav.php'; ?>
    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-12">
                <h4 class="fw-bold mt-4 mb-0">Bonjour, <?=htmlspecialchars($_SESSION['nom']) ?> 👋</h4>
                <p class="text-muted">Bienvenue dans votre espace personnel.</p>
            </div>
                            <!-- sttut aujourd'hui -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold text-muted mb-3">🕐 Ouverture aujourd'hui — <?= $auj ?></h6>
                        <?php if (!$horaire || $horaire['ferme']): ?>
                            <div class="alert alert-danger mb-0">Fermé aujourd'hui</div>
                        <?php else: ?>
                            <div class="alert alert-success mb-2">Ouvert de <strong><?= $horaire['heure_ouverture'] ?></strong> à <strong><?= $horaire ['heure_fermeture'] ?></strong></div>
                        <?php endif; ?>
                        <a href="menu.php" class="btn w-100 fw-bold text-white mt-2" style="background:#C9973D;">Commander maintenant</a>
                    </div><!--card-body-->
                </div> <!--card-->
            </div><!--col-->

                            <!-- stats rapide-->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold text-muted.mb-3">📊 Résumé</h6>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span>Commandes passées</span><strong><?= $total_cmd ?></strong>
                            </div>
                        <?php
                        $en_cours = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE user_id=? AND statut IN ('en attente','en préparation','prêt')");
                        $en_cours->execute([$_SESSION['user_id']]);
                        $en_cours = $en_cours->fetchColumn();
                        ?>
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span>Commandes en cours</span>
                            <strong><?= $en_cours ?></strong>
                        </div>
                        <a href="order.php" class="btn btn-outline-secondary w-100 mt-2 btn-sm">Voir mes commandes</a>
                    </div> <!-- cardbody-->
                </div> <!-- card -->
            </div> <!-- col -->

                            <!-- liens rapides -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold text-muted mb-3">🔗 Accès rapide</h6>
                        <div class="d-grid gap-2">
                            <a href="menu.php" class="btn btn-outline-dark text-start"><i class="bi bi-cart me-2"></i>Passer une commande</a>
                            <a href="orders.php" class="btn btn-outline-dark text-start"><i class="bi bi-box-seam me-2"></i>Suivre mes commandes</a>
                            <a href="profile.php" class="btn btn-outline-dark text-start"><i class="bi bi-person me-2"></i>Mon profil & Avis</a>
                            <a href="../index.php" class="btn btn-outline-dark text-start"><i class="bi bi-house me-2"></i>Retour à l'accueil</a>
                        </div>
                    </div> <!--card-body -->
                </div> <!-- card -->
            </div> <!-- col -->

                            <!-- dernieres commandes -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between">
                        <span>📦 Mes dernières commandes</span>
                        <a href="order.php" class="btn btn-sm btn-outline-primary">Toutes mes commandes</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light"><tr><th>#</th><th>Date</th><th>Total</th><th>Statut</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($mes_cmd as $c): ?>
                                <tr>
                                    <td>#<?= $c['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                                    <td class="fw-bold"><?= number_format($c['total'],2,',',' ') ?> €</td>
                                    <td><?= statutBadge($c['statut']) ?></td>
                                    <td><a href="orders.php" class="btn btn-sm btn-outline-primary">Suivi</a></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (!$mes_cmd): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Aucune commande. 
                                            <a href="menu.php">Commander</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                         </table>
                    </div> <!-- card-body -->
                </div> <!-- card -->
            </div> <!-- col -->
        </div> <!--row-->
    </div><!--container-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>