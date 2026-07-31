<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../repositories/ScheduleRepository.php';
require_once __DIR__ . '/../services/ScheduleService.php';

$scheduleRepository = new ScheduleRepository($pdo);
$scheduleService = new ScheduleService($scheduleRepository);

checkAdmin();

$active_page = "schedules";

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = $scheduleService->save($_POST['h']);
}

$horaires = $scheduleRepository->getAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horaires – Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/admin_nav.php'; ?>

    <div class="container py-4">
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
<script src="<?= BASE_URL ?>assets/js/shared/schedules.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>