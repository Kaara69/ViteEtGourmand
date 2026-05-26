<?php
// Démarre la session seulement si elle n’était pas déjà activée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// FONCTIONS D’ACCÈS


/**
 * Redirige vers la page de login si l'utilisateur n'est pas connecté.
 * @param string $redirect URL de redirection (par défaut: '../login.php')
 */
function checkLogin($redirect = '../login.php') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit;
    }
}


/**
 * Vérifie qu'on est connecté ET que le rôle est 'admin'.
 * Si non, redirige vers l'accueil.
 * @param string $redirect URL de redirection si pas connecté (par défaut: '../login.php')
 */
function checkAdmin($redirect = '../login.php') {
    checkLogin($redirect);

    if ($_SESSION['role'] !== 'admin') {
        header('Location: ../index.php');
        exit;
    }
}


/**
 * Vérifie qu'on est connecté ET que le rôle est 'admin' ou 'employee'.
 * Si non, redirige vers l'accueil.
 * @param string $redirect URL de redirection si pas connecté (par défaut: '../login.php')
 */
function checkAdminOrEmployee($redirect = '../login.php') {
    checkLogin($redirect);

    if (!in_array($_SESSION['role'], ['admin', 'employee'])) {
        header('Location: ../index.php');
        exit;
    }
}


// AIDE POUR L’AFFICHAGE


/**
 * Renvoie un badge Bootstrap correspondant à un statut.
 * @param string $s Statut ('en attente', 'accepté', etc.)
 * @return string HTML du badge
 */
function statutBadge($s) {
    $map = [
        'en attente'                   => 'warning',
        'accepté'                      => 'primary',
        'en préparation'               => 'info',
        'prêt'                         => 'success',
        'livré'                        => 'secondary',
        'annulé'                       => 'danger',
        'en attente du retour de matériel' => 'dark',
    ];
    $class = $map[$s] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . htmlspecialchars($s) . '</span>';
}


/**
 * Affiche une ligne de 5 étoiles (★ / ☆) selon la note.
 * @param int $n Note (forcée entre 1 et 5)
 * @return string Chaîne d’étoiles HTML
 */
function stars($n) {
    $n = max(1, min(5, (int)$n));
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}


/**
 * Formate un prix en français (deux décimales, virgule, espace mille, « € »).
 * @param float $p Prix
 * @return string Prix formaté
 */
function prix($p) {
    return number_format($p, 2, ',', ' ') . ' €';
}