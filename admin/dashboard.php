<?php
session_start();
include __DIR__ . '/../includes/auth.php';
checkAdmin();
include __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/nosql_db.php';
$active_page = 'dashboard';

$stats = [
    'total_cmd'    => $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn(),
    'en_attente'   => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en attente'")->fetchColumn(),
    'en_prep'      => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en préparation'")->fetchColumn(),
    'revenus'      => $pdo->query("SELECT COALESCE(SUM(total),0) FROM commandes WHERE statut NOT IN ('annulé')")->fetchColumn(),
    'clients'      => $pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn(),
    'avis_attente' => $pdo->query("SELECT COUNT(*) FROM avis WHERE statut='en attente'")->fetchColumn(),
];
$recentes = $pdo->query("SELECT c.*,u.nom as client FROM commandes c JOIN users u ON u.id=c.user_id ORDER BY c.created_at DESC LIMIT 8")->fetchAll();

$nosql_dash = new NoSQLStore();
$top_menus  = $nosql_dash->find('stats_menu');
usort($top_menus, fn($a,$b) => $b['nb_commandes'] <=> $a['nb_commandes']);
$top3 = array_slice($top_menus, 0, 3);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/admin_nav.php'; ?>
    <div class="container-fluid py-4">
        <h4 class="mb-4 fw-bold">Tableau de bord &nbsp;</h4>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-2">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body">
                        <div class="fs-2 fw-bold"><?= $stats['total_cmd'] ?></div>
                        <div class="small">Commandes total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-dark bg-warning h-100">
                    <div class="card-body">
                        <div class="fs-2 fw-bold"><?= $stats['en_attente'] ?></div>
                        <div class="small">En attente</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-white bg-info h-100">
                    <div class="card-body">
                        <div class="fs-2 fw-bold"><?= $stats['en_prep'] ?></div>
                        <div class="small">En preparation</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-white bg-success h-100">
                    <div class="card-body">
                        <div class="fs-2 fw-bold"><?= number_format($stats['revenus'],2,',',' ') ?> €</div>
                        <div class="small">Revenus</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-white bg-secondary h-100">
                    <div class="card-body">
                        <div class="fs-2 fw-bold"><?= $stats['clients'] ?></div>
                        <div class="small">Clients</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-white bg-danger h-100">
                    <div class="card-body">
                        <div class="fs-2 fw-bold"><?= $stats['avis_attente'] ?></div>
                        <div class="small">Avis en attente</div>
                    </div>
                </div>
            </div>
        </div> <!-- row -->

        <div class="card mb-4 border-0" style="background:#1C1510;">
            <div class="card-body py-3 px-4">
                <div class="row align-items-center g-3">
                    <div class="col-auto"><span style="color:#C9973D;font-size:1.3rem;">📊</span></div>
                    <div class="col">
                        <span class="fw-bold text-white small">Top menus 
                            <span class="badge ms-1" style="background:#C9973D;font-size:.65rem;letter-spacing:1px;">NoSQL</span> :
                        </span>
                        <?php if ($top3): ?>
                        <?php foreach ($top3 as $i => $t): ?>
                        <span class="ms-3 small" style="color:rgba(255,255,255,.85);"><?= ['🥇','🥈','🥉'][$i] ?> <?= htmlspecialchars(mb_substr($t['nom_menu'],0,22)) ?>
                            <span style="color:#C9973D;"> — <?= number_format($t['chiffre_affaires'],2,',',' ') ?> €</span>
                        </span>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <span class="text-muted small ms-2">Pas encore de données. 
                            <a href="stats.php?sync=1" style="color:#C9973D;">Initialiser les stats</a>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto">
                        <a href="stats.php" class="btn btn-sm fw-bold" style="background:#C9973D;color:#fff;">Statistiques complètes →</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Commandes récentes</strong>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">Toutes les commandes</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentes as $r): ?>
                                <tr>
                                    <td>#<?= $r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['client']) ?></td>
                                    <td><?= number_format($r['total'],2,',',' ') ?> €</td>
                                    <td><?= statutBadge($r['statut']) ?></td>
                                    <td class="small text-muted"><?= date('d/m H:i', strtotime($r['created_at'])) ?></td>
                                    <td><a href="orders.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (!$recentes): ?><tr><td colspan="6" class="text-center text-muted py-3">Aucune commande</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Avis en attente</strong>
                        <a href="reviews.php" class="btn btn-sm btn-outline-danger">Gérer</a>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $avis_attente = $pdo->query("SELECT * FROM avis WHERE statut='en attente' ORDER BY created_at DESC LIMIT 5")->fetchAll();
                        ?>
                        <?php if ($avis_attente): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($avis_attente as $a): ?>
                                <li class="list-group-item">
                                    <div class="fw-semibold small"><?= htmlspecialchars($a['nom']) ?> — <span style="color:#C9973D;"><?= stars($a['note']) ?></span></div>
                                    <div class="text-muted small text-truncate"><?= htmlspecialchars($a['contenu']) ?></div>
                                    <div class="mt-1 d-flex gap-1">
                                        <a href="reviews.php?approve=<?= $a['id'] ?>" class="btn btn-success btn-sm py-0">✓ Approuver</a>
                                        <a href="reviews.php?reject=<?= $a['id'] ?>"  class="btn btn-danger  btn-sm py-0">✗ Refuser</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-muted text-center py-3 small">Aucun avis en attente.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div> <!-- row g4 -->
    </div> <!-- container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>