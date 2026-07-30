<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../repositories/ReviewRepository.php';

$reviewRepository = new ReviewRepository($pdo);
checkAdminOrEmployee();
if ($_SESSION['role']==='admin') { header('Location: ../admin/reviews.php'); exit; }

$active_page = 'reviews';

// Actions rapides
if (isset($_GET['approve'])) {
    $reviewRepository->approve((int)$_GET['approve']);
    header('Location: reviews.php?filtre=en attente&ok=approuvé');
    exit;
}

if (isset($_GET['reject'])) {
    $reviewRepository->reject((int)$_GET['reject']);
    header('Location: reviews.php?filtre=en attente&ok=refusé');
    exit;
}

if (isset($_GET['delete'])) {
    $reviewRepository->delete((int)$_GET['delete']);
    header('Location: reviews.php?ok=supprimé');
    exit;
}

$filtre = $_GET['filtre'] ?? 'tous';
$counts = $reviewRepository->getCounts();
$avis = $reviewRepository->getAllWithUsers(
$filtre === 'tous' ? null : $filtre
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis – Employé</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/employee_nav.php'; ?>
    <div class="container-fluid py-4">
        <h4 class="fw-bold mb-3">Modération des avis clients</h4>
        <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success">Avis <?= htmlspecialchars($_GET['ok']) ?>.</div>
        <?php endif; ?>

        <div class="mb-3 d-flex flex-wrap gap-2">
            <?php
            $labels = ['tous'=>'Tous','en attente'=>'En attente','approuvé'=>'Approuvés','refusé'=>'Refusés'];
            $colors = ['tous'=>'dark','en attente'=>'warning','approuvé'=>'success','refusé'=>'danger'];
            foreach ($labels as $f => $label): ?>
            <a href="?filtre=<?= urlencode($f) ?>" class="btn btn-sm btn-<?= $filtre===$f?$colors[$f]:'outline-'.$colors[$f] ?>">
                <?= $label ?> <span class="badge bg-light text-dark ms-1"><?= $counts[$f]??0 ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (!$avis): ?>
            <div class="alert alert-info">Aucun avis dans cette catégorie.</div>
        <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>Auteur</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Statut</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avis as $a): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($a['nom']) ?></div>
                                <?php if ($a['email']): ?><div class="text-muted small"><?= htmlspecialchars($a['email']) ?></div><?php endif; ?>
                            </td>
                            <td><span class="stars"><?= stars($a['note']) ?></span></td>
                            <td class="small" style="max-width:300px;"><?= htmlspecialchars($a['contenu']) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                            <td>
                                <?php
                                    $sc = ['en attente'=>'warning','approuvé'=>'success','refusé'=>'danger'];
                                    echo '<span class="badge bg-'.($sc[$a['statut']]??'secondary').'">'.htmlspecialchars($a['statut']).'</span>';
                                ?>
                            </td>
                            <td>
                                <?php if ($a['statut'] !== 'approuvé'): ?>
                                    <a href="?approve=<?= $a['id'] ?>&filtre=<?= urlencode($filtre) ?>" class="btn btn-sm btn-success">✓ Approuver</a>
                                <?php endif; ?>
                                <?php if ($a['statut'] !== 'refusé'): ?>
                                    <a href="?reject=<?= $a['id'] ?>&filtre=<?= urlencode($filtre) ?>" class="btn btn-sm btn-warning ms-1">✗ Refuser</a>
                                <?php endif; ?>
                                <a href="?delete=<?= $a['id'] ?>&filtre=<?= urlencode($filtre) ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Supprimer définitivement ?')">🗑</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>