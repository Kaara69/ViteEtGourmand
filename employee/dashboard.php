<?php
session_start();
include __DIR__ . '/../includes/auth.php';
checkAdminOrEmployee();
if ($_SESSION['role']==='admin') { header('Location: ../admin/dashboard.php'); exit; }
include __DIR__ . '/../includes/db.php';
$active_page='dashboard';
$stats=[
    'total' => $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn(),
    'att'   => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en attente'")->fetchColumn(),
    'prep'  => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en preparation'")->fetchColumn(),
    'pret'  => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='pret'")->fetchColumn(),
];
$recentes=$pdo->query("SELECT c.*,u.nom as client FROM commandes c JOIN users u ON u.id=c.user_id ORDER BY c.created_at DESC LIMIT 8")->fetchAll();
$avis_att=$pdo->query("SELECT * FROM avis WHERE statut='en attente' ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Employe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/espace.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/employee_nav.php'; ?>
    <div class="contrainer-fluid py-4">
        <h4 class="mb-4 fw-bold">Bonjour, <?= htmlspecialchars($_SESSION['nom']) ?></h4>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3"><div class="card text-white bg-primary h-100"><div class="card-body"><div class="fs-2 fw-bold"><?= $stats['total'] ?></div><div class="small">Commandes total</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card text-dark bg-warning h-100"><div class="card-body"><div class="fs-2 fw-bold"><?= $stats['att'] ?></div><div class="small">En attente</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card text-white bg-info h-100"><div class="card-body"><div class="fs-2 fw-bold"><?= $stats['prep'] ?></div><div class="small">En preparation</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card text-white bg-success h-100"><div class="card-body"><div class="fs-2 fw-bold"><?= $stats['pret'] ?></div><div class="small">Pret</div></div></div></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between"><strong>Commandes récentes</strong><a href="orders.php" class="btn btn-sm btn-outline-primary">Toutes</a></div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light"><tr><th>#</th><th>Client</th><th>Total</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach($recentes as $r): ?>
                                    <tr>
                                        <td>#<?= $r['id'] ?></td><td><?= htmlspecialchars($r['client']) ?></td>
                                        <td><?= number_format($r['total'],2,',',' ') ?> €</td>
                                        <td><?= statutBadge($r['statut']) ?></td>
                                        <td class="small text-muted"><?= date('d/m H:i',strtotime($r['created_at'])) ?></td>
                                        <td><a href="orders.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a></td> 
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(!$recentes): ?><tr><td colspan="6" class="text-center text-muted py-3">Aucune commande</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between"><strong>Avis en attente</strong><a href="reviews.php" class="btn btn-sm btn-outline-danger">Gérer</a></div>
                    <div class="card-body p-0">
                        <?php if($avis_att): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach($avis_att as $a): ?>
                            <li class="list-group-item">
                                <div class="fw-semibold small"><?= htmlspecialchars($a['nom']) ?> — <span style="color:#C9973D;"><?= stars($a['note']) ?></span></div>
                                <div class="text-muted small text-truncate"><?= htmlspecialchars($a['contenu']) ?></div>
                                <div class="mt-1 d-flex gap-1">
                                    <a href="reviews.php?approve=<?= $a['id'] ?>" class="btn btn-success btn-sm py-0">✓</a>
                                    <a href="reviews.php?reject=<?= $a['id'] ?>"  class="btn btn-danger  btn-sm py-0">✗</a>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?><p class="text-muted text-center py-3 small">Aucun avis en attente.</p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>