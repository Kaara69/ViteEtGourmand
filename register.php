<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include 'includes/db.php';
include 'includes/partials/public_nav.php';

// Message d'erreur, vide au départ
$error = '';

// Vérifier si le formulaire a été envoyé (méthode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupérer les données du formulaire (avec ?? pour éviter les undefined index)
    $prenom     = trim($_POST['prenom']   ?? '' );
    $nom        = trim($_POST['nom']       ?? '');
    $email      = trim($_POST['email']     ?? '');
    $adresse    = trim($_POST['adresse']   ?? '');
    $telephone  = trim($_POST['telephone'] ?? '');
    $pass       =      $_POST['password']  ?? '';
    $pass2      =      $_POST['password2'] ?? '';

    // 2. Vérifier que les champs obligatoires ne sont pas vides
    if (empty($prenom) || empty($nom) || empty($email) || empty($adresse) || empty($telephone) || empty($pass)) {
        $error = 'Tous les champs sont obligatoires.';
    }

    // 3. V"rifier que l'email est valide
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = 'L\'adresse mail est invalide.';
        }

    // 4. Vérifier que le téléphone est valide 
    elseif (!preg_match('/^[0-9]{10,15}$/', $telephone)){
        $error = 'Le numéro de téléphone est invalide.';
    }

    // 5. Vérifier qla force du mdp 
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\\W_]).{10,}$/', $pass)) {
        $error = 'Le mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
    }

    // 4. Vérifier que les deux mots de passe sont identiques
    elseif ($pass !== $pass2) {
        $error = 'Les mots de passe ne correspondent pas.';
    }
    // 5. Si tout est OK, on tente d’enregistrer l’utilisateur
    else {
        try {
            // Hasher le mot de passe
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            // Insérer le nouvel utilisateur
            $sql = "INSERT INTO users (prenom, nom, email, adresse, telephone, password, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'client')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$prenom, $nom, $email, $adresse, $telephone, $hash]);

            // Recharger les infos de l’utilisateur
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            // Connecter l’utilisateur automatiquement
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            // Rediriger vers le dashboard
            header('Location: user/dashboard.php');
            exit;
        }
        catch (PDOException $e) {
            // Gérer le cas où l’email existe déjà
            $error = 'Cette adresse email est déjà utilisée.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription – Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Nunito:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/public.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none" style="color:#C9973D;font-family:'Playfair Display',serif;font-size:1.4rem;">Vite &amp; Gourmand</a>
                </div>
                <div class="card shadow-sm p-4 mb-5">
                    <h4 class="fw-bold mb-1">Créer un compte</h4>
                    <p class="text-muted small-mb-4">Inscrivez-vous pour commander en ligne et suivre vos commandes.</p>
                    <?php if($error):?>
                        <div class="alert alert-danger"> <?=htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nom*</label>
                            <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Prénom*</label>
                            <input type="text" name="prenom" class="form-control" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Adresse postale*</label>
                            <input type="text" name="adresse" class="form-control" required value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Numéro de téléphone*</label>
                            <input type="tel" name="telephone" class="form-control" required value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Adresse email*</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mot de passe * <small class="text-muted">(min. 6 caractères)</small></label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Confirmer le mot de passe *</label>
                            <input type="password" name="password2" class="form-control" required>
                        </div>
                        <button type="submit" class="btn w-100 fw-bold text-white" style="background:#C9973D;">Créer mon compte</button>
                    </form>
                    <hr>
                    <p class="text-center mb-0 small">Déjà un compte ? <a href="login.php" style="color:#C9973D">Se connecter</a></p>
                </div> <!-- card -->
            </div> <!-- col -->
        </div> <!-- row -->
    </div> <!-- container -->
<?php include 'includes/partials/footer.php';?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>