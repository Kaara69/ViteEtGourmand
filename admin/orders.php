<?php
session_start();
include '../include/auth.php';
checkAdminOrEmployee();
include '../include/db.php';
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