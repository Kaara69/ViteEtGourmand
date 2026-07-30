<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';

$orderRepository = new OrderRepository($pdo);

checkAdminOrEmployee();

if ($_SESSION['role']==='admin') 
    { header('Location: ../admin/orders.php'); 
exit;}

$active_page = 'orders';

// ajax statut
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax_statut'])) {
    $orderRepository->updateStatus(
    (int)$_POST['id'],
    $_POST['statut']
);
    echo json_encode(['ok'=>true]); exit;
}
// Suppression
if (isset($_GET['delete'])) {
    $orderRepository->delete((int)$_GET['delete']);
    header('Location: orders.php?ok=1'); exit;
}

$filtre = $_GET['filtre'] ?? 'tous';

$statuts = [
    'en attente',
    'accepté',
    'en préparation',
    'prêt',
    'livré',
    'en attente du retour de matériel',
    'annulé'
];

$commandes = $orderRepository->getAllWithUsers(
    $filtre === 'tous' ? null : $filtre
);

// Détail
$detail = null;
$detail_items = [];

if (isset($_GET['id'])) {

    $detail = $orderRepository->getWithUser((int)$_GET['id']);

    if ($detail) {
        $detail_items = $orderRepository->getItemsForOrder($detail['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes – Employe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/employee_nav.php'; ?>
    <div class="container-fluid py-4">
        <h4 class="fw-bold mb-3">Gestion des commandes</h4>
        <?php if (isset($_GET['ok'])): ?> <div class="alert alert-success">Commande supprimée</div><?php endif; ?>
         
        <?php if ($detail): ?>
        <div class="card mb-4 border-primary" id="detail-panel">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <span><strong>Commande #<? $detail['id']?></strong>— <?= htmlspecialchars($detail['client']) ?> (<?= htmlspecialchars($detail['email']) ?>)</span>
                <a href="orders.php<?= $filtre!=='tous'?'?filtre='.urlencode($filtre):'' ?>" class="btn btn-sm btn-light">✕ Fermer</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th>Article</th><th>Qté</th><th>Prix unit.</th><th>Sous-total</th></tr></thead>
                            <tbody>
                                <?php foreach ($detail_items as $it): ?>
                                <tr><td><?= htmlspecialchars($it['nom_menu']) ?></td><td><?= $it['quantite'] ?></td><td><?= number_format($it['prix_unitaire'],2,',',' ') ?> €</td><td><?= number_format($it['quantite']*$it['prix_unitaire'],2,',',' ') ?> €</td></tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot><tr class="fw-bold"><td colspan="3">Total</td><td><?= number_format($detail['total'],2,',',' ') ?> €</td></tr></tfoot> 
                        </table>
                        <?php if ($detail['notes']): ?><p class="text-muted small"><strong>📝 Notes :</strong> <?= htmlspecialchars($detail['notes']) ?></p><?php endif; ?>
                    </div>
                    <p class="mb-1"><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($detail['created_at'])) ?></p>
                    <p class="mb-1"><strong>Statut :</strong> <?= statutBadge($detail['statut']) ?></p>
                    <hr class="my-2">
                    <p class="mb-1 small"><strong>📧 Email :</strong> <?= htmlspecialchars($detail['email']) ?></p>
                    <?php if (!empty($detail['telephone'])): ?>
                    <p class="mb-1 small"><strong>📞 Téléphone :</strong> <?= htmlspecialchars($detail['telephone']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($detail['client_adresse'])): ?>
                    <p class="mb-1 small"><strong>🏠 Adresse client :</strong> <?= htmlspecialchars($detail['client_adresse']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($detail['date_evenement'])): ?>
                    <p class="mb-1 small fw-bold" style="color:var(--gold);">
                        <i class="bi bi-calendar-event me-1"></i>Date de l'événement :
                        <?= date('d/m/Y', strtotime($detail['date_evenement'])) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($detail['adresse_livraison'])): ?>
                    <p class="mb-1 small"><strong>📍 Livraison :</strong> <?= htmlspecialchars($detail['adresse_livraison']) ?>
                        <?php if ((float)($detail['km_livraison'] ?? 0) > 0): ?>
                        <span class="text-primary">(<?= round((float)$detail['km_livraison'],1) ?> km — <?= number_format($detail['frais_livraison'],2,',',' ') ?> €)</span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($detail['nb_personnes']) && $detail['nb_personnes'] > 1): ?>
                    <p class="mb-1 small"><strong>👥 Personnes :</strong> <?= (int)$detail['nb_personnes'] ?></p>
                    <?php endif; ?>
                    <?php if ($detail['notes']): ?>
                    <p class="mb-1 small"><strong>📝 Notes :</strong> <?= htmlspecialchars($detail['notes']) ?></p>
                    <?php endif; ?>
                    <hr class="my-2">
                    <label class="form-label fw-semibold">Changer le statut :</label>
                    <select class="form-select statut-select" data-id="<?= $detail['id'] ?>">
                        <?php foreach ($statuts as $s): ?><option value="<?= $s ?>" <?= $s===$detail['statut']?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
                    </select>
                    <div class="statut-feedback text-success mt-1 small"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="mb-3 d-flex flex-wrap gap-1">
            <?php foreach (array_merge(['tous'],$statuts) as $f): ?>
            <a href="?filtre=<?= urlencode($f) ?><?= isset($_GET['id'])?'&id='.(int)$_GET['id']:'' ?>" class="btn btn-sm <?= $filtre===$f?'btn-dark':'btn-outline-secondary' ?>"><?= ucfirst($f) ?></a>
            <?php endforeach; ?>
            <span class="ms-2 text-muted align-self-center small"><?= count($commandes) ?> commande<?= count($commandes)>1?'s':'' ?></span>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>#</th><th>Client</th><th>Total</th><th>Articles</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($commandes as $c):
                        $nb = $pdo->prepare("SELECT SUM(quantite) FROM commande_items WHERE commande_id=?");
                        $nb->execute([$c['id']]); $nbi = (int)$nb->fetchColumn();
                        ?>
                        <tr>
                            <td>#<?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['client']) ?></td>
                            <td class="fw-bold"><?= number_format($c['total'],2,',',' ') ?> €</td>
                            <td><span class="badge bg-secondary"><?= $nbi ?> art.</span></td>
                            <td>
                            <select class="form-select form-select-sm statut-select" data-id="<?= $c['id'] ?>" style="min-width:140px">
                                <?php foreach ($statuts as $s): ?><option value="<?= $s ?>" <?= $s===$c['statut']?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
                            </select>
                            <div class="statut-feedback text-success small"></div>
                            </td>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                            <td>
                            <a href="?id=<?= $c['id'] ?>&filtre=<?= urlencode($filtre) ?>#detail-panel" class="btn btn-sm btn-outline-primary">Détail</a>
                            <a href="?delete=<?= $c['id'] ?>&filtre=<?= urlencode($filtre) ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Supprimer cette commande ?')">Suppr.</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$commandes): ?><tr><td colspan="7" class="text-center text-muted py-4">Aucune commande.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> <!-- container -->
 
<script src="<?= BASE_URL ?>assets/js/shared/orders.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>