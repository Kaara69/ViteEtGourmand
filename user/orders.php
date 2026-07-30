<?php
session_start();
include __DIR__ . '/../includes/auth.php';
checkLogin('../login.php');
include __DIR__ . '/../includes/db.php';

$active_page = 'orders';

// ── Annuler une commande ──────────────────────────────────────
if (isset($_POST['cancel_order'])) {
    $cid = (int)$_POST['cancel_order'];
    // Vérifie que la commande appartient bien à l'utilisateur ET est encore "en attente"
    $check = $pdo->prepare("SELECT id, statut FROM commandes WHERE id=? AND user_id=?");
    $check->execute([$cid, $_SESSION['user_id']]);
    $cmd = $check->fetch();
    if ($cmd && $cmd['statut'] === 'en attente') {
        $pdo->prepare("UPDATE commandes SET statut='annulé' WHERE id=?")->execute([$cid]);
        $cancel_msg = "Commande #$cid annulée avec succès.";
    } else {
        $cancel_error = "Impossible d'annuler cette commande (déjà prise en charge ou introuvable).";
    }
}

$stmt = $pdo->prepare("SELECT * FROM commandes WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll();

$details = [];
foreach ($commandes as $c) {
    $si = $pdo->prepare("SELECT * FROM commande_items WHERE commande_id=?");
    $si->execute([$c['id']]);
    $details[$c['id']] = $si->fetchAll();
}
$ids = array_column($commandes, 'id');

function renderTrack($statut, $id) {
    $steps = ['en attente','accepté', 'en préparation','prêt','livré', 'en attente du retour de matériel'];
    $icons = ['⏳','✅', '👨‍🍳','✅','🚀','⏳'];
    $cur = array_search($statut, $steps);
    $out = '<div class="progress-track">';
    foreach ($steps as $i => $s) {
        if ($statut==='annulé') { $cls='todo'; $dotcls='dot-todo'; }
        elseif ($i < $cur)     { $cls='done'; $dotcls='dot-done'; }
        elseif ($i===$cur)     { $cls='active'; $dotcls='dot-active'; }
        else                   { $cls='todo'; $dotcls='dot-todo'; }
        $out .= '<div class="step-dot '.$cls.'"><div class="dot '.$dotcls.'">'.$icons[$i].'</div><div>'.ucfirst($s).'</div></div>';
    }
    $out .= '</div>';
    if ($statut==='annulé') $out .= '<div class="alert alert-danger py-1 px-2 small mt-1"><i class="bi bi-x-circle me-1"></i>Commande annulée</div>';
    return $out;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes – Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/partials/user_nav.php'; ?>

    <div class="container py-4">
        <?php if (!empty($cancel_msg)): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <?= htmlspecialchars($cancel_msg) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($cancel_error)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= htmlspecialchars($cancel_error) ?>
        </div>
        <?php endif; ?>


        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Mes commandes</h4>
            <div class="f-flex align-items-center gap-2">
                <span class="badge" id="live-dot" style="background:var(--dark);color:var(--gold);">🔴 Suivi en direct</span>
                <a href="menu.php" class="btn btn-sm fw-bold text-white" style="background:var(--gold);">
                    <i class="bi bi-plus-lg me-1"></i>Nouvelle commande
                </a>
            </div>
        </div>

        <?php if (!$commandes): ?>
        <div class="text-center py-5">
            <p style="font-size:3rem;">🛒</p>
            <p class="text-muted">Vous n'avez pas encore passé de commande.</p>
            <a href="menu.php" class="btn fw-bold text-white" style="background:var(--gold);">Commander maintenant</a>
        </div>
        <?php else: ?>
            
        <div class="accordion d-flex flex-column gap-3" id="#ordersAccordion">
            <?php foreach ($commandes as $idx => $c):
            $peut_annuler = ($c['statut'] === 'en attente');
            ?>
            <div class="accordion-item border-0 shadow-sm" id="order-wrap-<?= $c['id'] ?>">
                <h2 class="accordion-header">
                    <button class="accordion-button <?= $idx>0?'collapsed':'' ?> fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#col<?= $c['id'] ?>">
                        <div class="d-flex justify-content-between w-100 me-3 flex-wrap gap-2 align-items-center">
                            <span>
                                <span class="text-muted small me-2">#<?= $c['id'] ?></span>
                                <?= date('d/m/Y à H:i', strtotime($c['created_at'])) ?>
                            </span>
                            <span class="d-flex align-items-center gap-2">
                                <strong><?= number_format($c['total'],2,',',' ') ?> €</strong>
                                <span id="badge-<?= $c['id'] ?>"><?= statutBadge($c['statut']) ?></span>
                                <?php if ($peut_annuler): ?>
                                <span class="badge bg-warning text-dark small">Annulable</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </button>
                </h2>

                <div id="col<?= $c['id'] ?>" class="accordion-collapse collapse <?= $idx===0?'show':'' ?>">
                    <div class="accordion-body bg-white">
                        <div class="row g-4">
                                            <!-- Détail articles -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">🧾 Détail de la commande</h6>
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr><th>Article</th><th>Qté</th><th>P.U.</th><th>Total</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php $subtotal_items = 0;
                                        foreach ($details[$c['id']] as $it):
                                            $pmin = (int)($it['personnes_min'] ?? 1);
                                            if ($pmin > 1) {
                                                // Menu collectif : prix par personne × nb personnes de la commande
                                                $pu_affiche = $it['prix_unitaire'] / $pmin;
                                                $line = $pu_affiche * $c['nb_personnes'] * $it['quantite'];
                                            } else {
                                                // Menu individuel classique
                                                $pu_affiche = $it['prix_unitaire'];
                                                $line = $it['quantite'] * $it['prix_unitaire'];
                                            }
                                            $subtotal_items += $line;
                                        ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($it['nom_menu']) ?>
                                                <?php if ($pmin > 1): ?>
                                                    <div class="text-muted" style="font-size:.72rem;">
                                                        <?= number_format($pu_affiche, 2, ',', ' ') ?> €/pers. × <?= (int)$c['nb_personnes'] ?> pers.
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $it['quantite'] ?></td>
                                            <td><?= number_format($pu_affiche,2,',',' ') ?> €<?= $pmin > 1 ? '<span class="text-muted" style="font-size:.7rem;">/pers.</span>' : '' ?></td>
                                            <td><?= number_format($line,2,',',' ') ?> €</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <?php if (!empty($c['remise']) && $c['remise'] > 0): ?>
                                        <tr class="text-success small"><td colspan="3">🏷️ Remise fidélité</td><td>- <?= number_format($c['remise'],2,',',' ') ?> €</td></tr>
                                        <?php endif; ?>
                                        <?php
                                            $km = round((float)($c['km_livraison'] ?? 0), 1);
                                            $km_label = $km > 0 ? " ({$km} km)" : ' (Bordeaux)';
                                        ?>
                                        <tr class="text-primary small"><td colspan="3">🚚 Livraison<?= $km_label ?></td><td><?= number_format($c['frais_livraison'] ?? 5,2,',',' ') ?> €</td></tr>
                                        <tr class="fw-bold table-light">
                                            <td colspan="3">Total TTC</td>
                                            <td style="color:#C9973D;"><?= number_format($c['total'],2,',',' ') ?> €</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php if (!empty($c['date_evenement'])): ?>
                                <p class="small fw-bold" style="color:var(--gold);">
                                    <i class="bi bi-calendar-event me-1"></i>Date de l'événement :
                                    <?= date('d/m/Y', strtotime($c['date_evenement'])) ?>

                                    <?php if (!empty($c['heure_evenement'])): ?>
                                        à <?= date('H:i', strtotime($c['heure_evenement'])) ?>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>

                                <?php if (!empty($c['nb_personnes']) && $c['nb_personnes'] > 1): ?>
                                <p class="text-muted small">
                                    <i class="bi bi-people me-1"></i>
                                    <strong>Nb personnes :</strong> <?= (int)$c['nb_personnes'] ?>
                                </p>
                                <?php endif; ?>

                                <?php if ($c['notes']): ?>
                                <p class="text-muted small">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    <strong>Notes :</strong> <?= htmlspecialchars($c['notes']) ?>
                                </p>
                                <?php endif; ?>
                            </div> <!-- col-md-6 -->

                                        <!-- Suivi + annulation -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-2">📍 Suivi en temps réel</h6>
                                <div id="track-<?= $c['id'] ?>">
                                    <?= renderTrack($c['statut'], $c['id']) ?>
                                </div>

                                <?php if ($peut_annuler): ?>
                                <div class="mt-3 p-3 border border-warning rounded-3" style="background:#fffdf5;">
                                    <p class="small fw-semibold mb-2">
                                        <i class="bi bi-info-circle text-warning me-1"></i>
                                        Cette commande est encore <strong>en attente</strong> et peut être annulée.
                                    </p>
                                    <form method="post"
                                        onsubmit="return confirm('Confirmer l\'annulation de la commande #<?= $c['id'] ?> ?\nCette action est irréversible.');">
                                        <input type="hidden" name="cancel_order" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold">
                                            <i class="bi bi-x-circle me-1"></i>Annuler cette commande
                                        </button>
                                    </form>
                                </div>
                                <?php elseif ($c['statut'] !== 'annulé'): ?>
                                <div class="mt-3 p-3 rounded-3 small text-muted" style="background:#f8f9fa;">
                                    <i class="bi bi-lock me-1"></i>
                                    Commande prise en charge — annulation impossible.
                                </div>
                                <?php endif; ?>
                            </div> <!-- col-md-6 -->
                        </div> <!-- row -->
                    </div> <!-- accordion-body -->
                </div> <!-- id=col -->
            </div> <!-- acordion-item -->
            <?php endforeach; ?>
        </div> <!-- accordion -->
            <?php endif; ?>
    </div> <!-- container -->


<script>
// Liste des IDs de cmd
// On récupère les IDs des cmd depuis PHP
const orderIds = <?= json_encode($ids) ?>;


// Fonction de maj des statuts (polling)
// Mets à jour les badges et barres de suivi de cmd
async function pollStatuts() {
  // Si l’utilisateur n’a aucune cmd, on ne fait rien
  if (!orderIds.length) return;

  try {
    // On demande à l’API les statuts des commandes
    // Exemple d’URL : `../api/order_status.php?ids=1,2,3`
    const url = '../api/order_status.php?ids=' + orderIds.join(',');

    const response = await fetch(url);
    const items = await response.json(); // { id, badge, track }

    // On met à jour chaque commande dans le DOM
    items.forEach(item => {
      const badgeEl = document.getElementById('badge-' + item.id);
      const trackEl = document.getElementById('track-' + item.id);

      // Mettre à jour le badge (état affiché)
      if (badgeEl) {
        badgeEl.innerHTML = item.badge;
      }

      // Mettre à jour le suivi de livraison
      if (trackEl) {
        trackEl.innerHTML = item.track;
      }
    });
  } catch (e) {
    // Si il y a une erreur (réseau, etc.), on l’ignore
    // le site continue de fonctionner
  }
}

// Appel régulier (polling) toutes les 5 secondes 
// On relance cette fonction toutes les 5000 ms
setInterval(pollStatuts, 5000);


// Animation du point “en direct”
let blink = true;

setInterval(() => {
  const dot = document.getElementById('live-dot');

  // Si l’élément existe, on alterne sa couleur
  if (dot) {
    dot.textContent = (blink ? '🔴' : '🔵') + ' Suivi en direct';
  }

  // Pour la prochaine fois, inverser la couleur
  blink = !blink;
}, 1500);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>