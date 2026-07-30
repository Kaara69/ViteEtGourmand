<?php
session_start();
include __DIR__ . '/../includes/auth.php';
checkAdmin();
include __DIR__ . '/../includes/db.php';
$active_page = 'employees';

$msg = ''; $error = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id=? AND role!='client'")->execute([$id]);
    }
    header('Location: employees.php?ok=1'); exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nom   = trim($_POST['nom']);
    $email = trim(strtolower($_POST['email']));
    $pass  = $_POST['password'];
    $role  = in_array($_POST['role'],['employee','admin']) ? $_POST['role'] : 'employee';
    if (!$nom||!$email||!$pass) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (strlen($pass)<6) {
        $error = 'Mot de passe trop court (6 caractères minimum).';
    } else {
        try {
            $pdo->prepare("INSERT INTO users (nom,email,password,role) VALUES (?,?,?,?)")
                ->execute([$nom,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
            $msg = 'Compte créé avec succès.';
        } catch (PDOException $e) {
            $error = 'Cet email est déjà utilisé.';
        }
    }
}
$staff = $pdo->query("SELECT * FROM users WHERE role IN ('admin','employee') ORDER BY role,nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employés – Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/admin_nav.php'; ?>
    <div class="container py-4">
        <h4 class="fw-bold mb-4">Getsion des employés &amp; administrateur</h4>
        <div class="row g-4">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header fw-bold">Créer un compte</div>
                    <div class="card-body">
                        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Nom complet *</label>
                                <input type="text" name="nom" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email (identifiant) *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mot de passe * <small class="text-muted">(min. 6 car.)</small></label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                             <div class="mb-3">
                                <label class="form-label">Rôle *</label>
                                    <select name="role" class="form-select">
                                        <option value="employee">Employé</option>
                                        <option value="admin">Administrateur</option>
                                    </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Créer le compte</button>
                        </form>
                    </div>
                </div> <!-- card -->
            </div>
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header fw-bold">Comptes staff (<?= count($staff) ?>)</div>
                    <?php if (isset($_GET['ok'])): ?><div class="alert alert-info m-2 py-2">Compte supprimé.</div><?php endif; ?>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $e): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($e['nom']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($e['email']) ?></td>
                                    <td><?= $e['role']==='admin' ? '<span class="badge bg-danger">Admin</span>' : '<span class="badge bg-primary">Employé</span>' ?></td>
                                    <td><?php if ($e['id']!=$_SESSION['user_id']): ?><a href="?delete=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ?')">Suppr.</a><?php else: ?><span class="text-muted small">Vous</span><?php endif; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- row -->
    </div> <!-- container -->
    
</body>
</html>