<?php
session_start();
include __DIR__ . '/../includes/auth.php';
checkAdminOrEmployee();
include __DIR__ . '/../includes/db.php';
$active_page = 'orders';

// AJAX statut
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax_statut'])) {
    $pdo->prepare("UPDATE commandes SET statut=? WHERE id=?")->execute([$_POST['statut'],(int)$_POST['id']]);
    echo json_encode(['ok'=>true]); exit;
}
// Suppression
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM commandes WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: orders.php?ok=1'); exit;
}

$filtre = $_GET['filtre'] ?? 'tous';
$statuts = ['en attente','accepté','en préparation','prêt','livré','en attente du retour de matériel','annulé'];
$where = ($filtre!=='tous') ? "WHERE c.statut=?" : "WHERE 1";
$params = ($filtre!=='tous') ? [$filtre] : [];
$stmt = $pdo->prepare("SELECT c.*,u.nom as client,u.email,u.telephone,u.adresse as client_adresse FROM commandes c JOIN users u ON u.id=c.user_id $where ORDER BY c.created_at DESC");
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Détail
$detail = null; $detail_items = [];
if (isset($_GET['id'])) {
    $s = $pdo->prepare("SELECT c.*,u.nom as client,u.email,u.telephone,u.adresse as client_adresse FROM commandes c JOIN users u ON u.id=c.user_id WHERE c.id=?");
    $s->execute([(int)$_GET['id']]);
    $detail = $s->fetch();
    if ($detail) {
        $si = $pdo->prepare("SELECT * FROM commande_items WHERE commande_id=?");
        $si->execute([$detail['id']]);
        $detail_items = $si->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes – Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/admin_nav.php'; ?>
    <div class="container-fluid py-4">
        <h4 class="fw-bold mb-3">Gestion des commandes</h4>
        <?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Commande supprimée.</div><?php endif; ?>
        
        <?php if ($detail): ?>
        <div class="card mb-4 border-primary" id="detail-panel">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <span><strong>Commande #<?= $detail['id'] ?></strong> — <?= htmlspecialchars($detail['client']) ?> (<?= htmlspecialchars($detail['email']) ?>)</span>
                <a href="orders.php<?= $filtre!=='tous'?'?filtre='.urlencode($filtre):'' ?>" class="btn btn-sm btn-light">✕ Fermer</a>
            </div>      
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Article</th>
                                    <th>Qté</th>
                                    <th>Prix unit.</th>
                                    <th>Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $subtotal_items = 0;
                                    foreach ($detail_items as $it):
                                    $line = $it['quantite']*$it['prix_unitaire'];
                                    $subtotal_items += $line;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($it['nom_menu']) ?></td>
                                    <td><?= $it['quantite'] ?></td>
                                    <td><?= number_format($it['prix_unitaire'],2,',',' ') ?> €</td>
                                    <td><?= number_format($line,2,',',' ') ?> €</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                             <tfoot>
                                <tr class="table-light">
                                    <td colspan="3" class="small text-muted">Sous-total articles</td>
                                    <td><?= number_format($subtotal_items,2,',',' ') ?> €</td>
                                </tr>
                                <?php if (!empty($detail['remise']) && $detail['remise'] > 0): ?>
                                <tr class="text-success">
                                    <td colspan="3" class="small">🏷️ Remise fidélité (10%)</td>
                                    <td>- <?= number_format($detail['remise'],2,',',' ') ?> €</td>
                                </tr>
                                <?php endif; ?>
                                <?php
                                    $km    = round((float)($detail['km_livraison'] ?? 0), 1);
                                    $km_label = $km > 0 ? " — {$km} km" : ' — Bordeaux';
                                ?>
                                <tr class="text-primary">
                                    <td colspan="3" class="small">🚚 Livraison<?= $km_label ?></td>
                                    <td><?= number_format($detail['frais_livraison'] ?? 5,2,',',' ') ?> €</td>
                                </tr>
                                <tr class="fw-bold">
                                    <td colspan="3">Total TTC</td>
                                    <td style="color:#C9973D;"><?= number_format($detail['total'],2,',',' ') ?> €</td>
                                </tr>
                            </tfoot>
                        </table>
                        <?php if (!empty($detail['date_evenement'])): ?>
                            <p class="small fw-bold" style="color:var(--gold);">
                                <i class="bi bi-calendar-event me-1"></i><strong>Date de l'événement :</strong>
                                <?= date('d/m/Y', strtotime($detail['date_evenement'])) ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($detail['nb_personnes']) && $detail['nb_personnes'] > 1): ?>
                            <p class="small text-muted"><strong>👥 Nombre de personnes :</strong> <?= (int)$detail['nb_personnes'] ?></p>
                        <?php endif; ?>
                        <?php if (!empty($detail['adresse_livraison'])): ?>
                            <p class="small text-muted"><strong>📍 Adresse de livraison :</strong> <?= htmlspecialchars($detail['adresse_livraison']) ?></p>
                        <?php endif; ?>
                        <?php if ($detail['notes']): ?><p class="text-muted small"><strong>📝 Notes :</strong> <?= htmlspecialchars($detail['notes']) ?></p><?php endif; ?>
                    </div> <!-- col -->
                    <div class="col-md-5">
                        <p class="mb-1"><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($detail['created_at'])) ?></p>
                        <p class="mb-1"><strong>Statut :</strong> <?= statutBadge($detail['statut']) ?></p>
                        <hr class="my-2">
                        <p class="mb-1 small"><strong>📧 Email client :</strong> <?= htmlspecialchars($detail['email']) ?></p>
                        <?php if (!empty($detail['telephone'])): ?>
                            <p class="mb-1 small"><strong>📞 Téléphone :</strong> <?= htmlspecialchars($detail['telephone']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($detail['client_adresse'])): ?>
                            <p class="mb-2 small"><strong>🏠 Adresse client :</strong> <?= htmlspecialchars($detail['client_adresse']) ?></p>
                        <?php endif; ?>
                        <hr class="my-2">
                        <label class="form-label fw-semibold">Changer le statut :</label>
                        <select class="form-select statut-select" data-id="<?= $detail['id'] ?>">
                            <?php foreach ($statuts as $s): ?><option value="<?= $s ?>" <?= $s===$detail['statut']?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
                        </select>
                        <div class="statut-feedback text-success mt-1 small"></div>
                    </div>
                </div> <!-- row -->
            </div> <!-- cardbody -->
         </div> <!-- card -->
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
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Total</th>
                            <th>Articles</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
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