<?php
session_start();
include 'include/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom   = trim($_POST['nom']);
    $email = trim(strtolower($_POST['email']));
    $pass  = $_POST['password'];
    $pass2 = $_POST['password2'];
    if (!$nom || !$email || !$pass) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (strlen($pass) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($pass !== $pass2) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        try {
            $pdo->prepare("INSERT INTO users (nom,email,password,role) VALUES (?,?,?,'client')")
                ->execute([$nom, $email, password_hash($pass, PASSWORD_DEFAULT)]);
// auto login
            $user = $pdo->prepare("SELECT * FROM users WHERE email=?");
            $user->execute([$email]);
            $user = $user->fetch();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = 'client';
            header('Location: user/dashboard.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Cette adresse email est déjà utilisée.';
        }
    }
}
?>
