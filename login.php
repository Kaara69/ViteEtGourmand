<?php
session_start();

// Si l'utilisateur est déjà connecté, on le redirige selon son rôle
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'client') {
        header('Location: user/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'employee') {
        header('Location: employee/dashboard.php');
        exit;
    } else {
        header('Location: admin/dashboard.php');
        exit;
    }
}
include 'include/db.php';

// Message d'erreur (vide au départ)
$error = '';

// On vérifie si le formulaire a été envoyé
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_saisi = trim($_POST['email'] ?? '');
    $password_saisi = $_POST['password'] ?? '';

    // On cherche un utilisateur avec cet email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email_saisi]);
    $user = $stmt->fetch();

    // Si on trouve un utilisateur et que le mot de passe est bon
    if ($user && password_verify($password_saisi, $user['password'])) {
        // On stocke les infos de l'utilisateur en session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        // On redirige selon le rôle
        if ($user['role'] === 'client') {
            header('Location: user/dashboard.php');
        } elseif ($user['role'] === 'employee') {
            header('Location: employee/dashboard.php');
        } else {
            header('Location: admin/dashboard.php');
        }
        exit;
    }

    // Si on arrive ici, c’est que l’email ou le mot de passe est incorrect
    $error = 'Email ou mot de passe incorrect.';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/public.css">
</head>
<body>
    <div class="row g-0 min-vh-100">
        <div class="col-lg-5 login-hero d-none d-lg-flex flex-column justify-content-center align-items-center text-white p-5">
            <h1 style="font-family:'Playfair Display, serif; color:#C9973D">Vite &amp; Gourmand</h1>
            <p class="text-center" style="color:rgba(255,255,255,.7); max-width:300px;">Connectez-vous pour accéder à votre espace.</p>
            <a href="index.php" class="btn mt-3" style="border:1px solid rgba(201,151,61,.5);color:#C9973D;">Accueil</a>
        </div>
        <div class="col-lg-7 d-flex align-items-center justify-content-center p-4">
            <div class="w-100" style="max-width:420px">
                <h3 class="fw-bold mb-1">Connexion</h3>
                <p class="text-muted mb-4">Connectez-vous avec votre adresse mail.</p>
                <?php if($error):?> <div class="alert alert-danger"><?= htmlspecialchars($error)?></div><?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Adresse email</label>
                            <input type="email" name="email" class="form-control form-control-lg" required autofocus value="<?= htmlspecialchars($_POST['email']??'') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Mots de passe</label>
                            <input type="password" name="password" class="form-control form-control-lg" required>
                        </div>
                        <button type="submit" class="btn btn-lg w-100 fw-bold text-white" style="background:#C9973D;">Se connecter</button>
                    </form>
                    <hr class="my-4">
                <p class="text-center mb-0">Pas de compte ? <a href="register.php" style="color:#C9973D;">S'inscrire</a></p>
                <p class="text-center mt-1"><a href="index.php" class="text-muted small"> Retour a l'accueil</a></p>
            </div> <!-- w-100 -->
        </div> <!-- col-->
    </div> <!-- row-->
    <?php include 'include/partials/footer.php'; ?>
</body>
</html>
