<?php   
session_start();
include 'include/db.php';

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
