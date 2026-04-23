<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo '[]'; exit; }

include '../include/db.php';
include '../include/auth.php';

header('Content-Type: application/json');

$ids_raw = $_GET['ids'] ?? '';
$ids = array_filter(array_map('intval', explode(',', $ids_raw)));
if (!$ids) { echo '[]'; exit; }

$ph = implode(',', array_fill(0, count($ids), '?'));

if ($_SESSION['role'] === 'client') {
    $stmt = $pdo->prepare("SELECT id,statut FROM commandes WHERE id IN ($ph) AND user_id=?");
    $stmt->execute(array_merge($ids, [$_SESSION['user_id']]));
} else {
    $stmt = $pdo->prepare("SELECT id,statut FROM commandes WHERE id IN ($ph)");
    $stmt->execute($ids);
}

function renderTrack($statut) {
    $steps = ['en attente','accepté','en préparation','prêt','livré'];
    $icons = ['⏳','✔️','👨‍🍳','✅','🚀'];
    // Pour le retour de matériel, on considère la commande comme "livrée" dans la barre
    $statut_track = ($statut === 'en attente du retour de matériel') ? 'livré' : $statut;
    $cur = array_search($statut_track, $steps);
    $out = '<div class="progress-track">';
    foreach ($steps as $i => $s) {
        if ($statut==='annulé') { $cls='todo'; $dc='dot-todo'; }
        elseif ($i < $cur) { $cls='done'; $dc='dot-done'; }
        elseif ($i===$cur) { $cls='active'; $dc='dot-active'; }
        else { $cls='todo'; $dc='dot-todo'; }
        $out .= '<div class="step-dot '.$cls.'"><div class="dot '.$dc.'">'.$icons[$i].'</div><div>'.ucfirst($s).'</div></div>';
    }
    $out .= '</div>';
    if ($statut==='annulé') {
        $out .= '<div class="alert alert-danger py-1 px-2 small mt-2">Commande annulée</div>';
    }
    if ($statut==='en attente du retour de matériel') {
        $out .= '<div class="alert alert-warning py-2 px-3 small mt-2">'
              . '<strong>📦 Retour de matériel en attente</strong><br>'
              . 'Du matériel prêté doit être restitué sous <strong>10 jours ouvrés</strong>. '
              . 'Passé ce délai, des frais de <strong>600 €</strong> seront appliqués '
              . '(cf. conditions générales de vente).<br>'
              . 'Pour organiser la restitution, contactez-nous : '
              . '<a href="mailto:contact@vitegourmand.fr">contact@vitegourmand.fr</a>'
              . '</div>';
    }
    return $out;
}

$result = [];
while ($row = $stmt->fetch()) {
    $result[] = [
        'id'    => $row['id'],
        'statut' => $row['statut'],
        'badge' => statutBadge($row['statut']),
        'track' => renderTrack($row['statut']),
    ];
}
echo json_encode($result);
