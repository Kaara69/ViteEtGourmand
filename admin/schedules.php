<?php
session_start();
include __DIR__ . '/../includes/auth.php';
checkAdmin();
include __DIR__ . '/../includes/db.php';
$active_page = "schedules";

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    foreach ($_POST['h'] as $jour => $data) {
        $ferme  = isset($data['ferme']) ? 1 : 0;
        $ouv    = $ferme ? '00:00' : $data['ouverture'];
        $ferme  = $ferme ? '00:00' : $data['fermeture'];
        $dp->prepare("UPDATE horaires SET heure_ouverture=?,heure_fermeture=? WHERE jour=?")
           ->execute([$ouv,$ferm,$ferme,$jour]);
    }
    $msg = 'Horaires enregistrés avec succès.';
}
$horaires = [];
foreach ($pdo->query("SELECT * FROM horaires ORDER BY id")->fetchAll() as $h) $horaires[$h['jour']] = $h;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horaires – Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/espace.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/admin_nav.php'; ?>

    <div class="container">
        <h4 class="fw-bold mb-4">Gestion des horaires d'ouverture</h4>
        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
        <div class="col-md-8">
            <form method="post">
                <div class="card">
                    <div class="card-body">
                        <table class="table align-middle">
                            <thead class="table-dark"><tr><th>Jour</th><th>Heure ouverture</th><th>Heure fermeture</th><th>Fermé</th></tr></thead>
                            <tbody>
                                <?php foreach ($horaires as $jour => $h): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($jour) ?></td>
                                    <td><input type="time" name="h[<?= $jour ?>][ouverture]" class="form-control form-control-sm" style="width:130px" value="<?= $h['heure_ouverture'] ?>" <?= $h['ferme']?'disabled':'' ?>></td>
                                    <td><input type="time" name="h[<?= $jour ?>][fermeture]" class="form-control form-control-sm" style="width:130px" value="<?= $h['heure_fermeture'] ?>" <?= $h['ferme']?'disabled':'' ?>></td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input toggle-ferme" type="checkbox" name="h[<?= $jour ?>][ferme]" <?= $h['ferme']?'checked':'' ?>>
                                            <label class="form-check-label">Fermé</label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">💾 Enregistrer les horaires</button>
            </form>
        </div>
    </div>
<script>
document.querySelectorAll('.toggle-ferme').forEach(cb => {
  cb.addEventListener('change', function() {
    const row = this.closest('tr');
    row.querySelectorAll('input[type="time"]').forEach(i => i.disabled = this.checked);
  });
});
</script>
</body>
</html>