<?php 
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../repositories/MenuRepository.php';

$menuRepository = new MenuRepository($pdo);

checkAdminOrEmployee();

    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/menus.php');
        exit;
    }

$active_page = 'menus';

$msg = '';
    if (isset($_GET['delete'])) {
        $menuRepository->delete((int)$_GET['delete']);
        header('Location: menus.php?ok=supprimé'); exit;
    }
    if (isset($_GET['toggle'])) {
        $menuRepository->toggleAvailability((int)$_GET['toggle']);
        header('Location: menus.php'); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $nom   = trim($_POST['nom']);
        $desc  = trim($_POST['description']);
        $prix  = (float)str_replace(',', '.', $_POST['prix']);
        $cat   = trim($_POST['categorie']);
        $dispo = isset($_POST['disponible']) ? 1 : 0;
        $image_url = trim($_POST['image_url'] ?? '');

        if (!empty($_POST['id'])) {

            $menuRepository->update(
                (int)$_POST['id'],
                $nom,
                $desc,
                $prix,
                $cat,
                $dispo,
                $image_url
            );

            $msg = 'Menu mis à jour avec succès.';

        } else {
            $menuRepository->create(
                $nom,
                $desc,
                $prix,
                $cat,
                $dispo,
                $image_url
            );

            $msg = 'Menu ajouté avec succès.';
        }
    }

    $edit = null;

    if (isset($_GET['edit'])) {
        $edit = $menuRepository->findById((int)$_GET['edit']);
    }

    $cats = $menuRepository->getCategories();
    $menus = $menuRepository->getAll();

    $by_cat = [];

    foreach ($menus as $m) {
        $by_cat[$m['categorie']][] = $m;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menus – Employe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
    </head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/partials/employee_nav.php'; ?>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Gestion des menus</h4>
            <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#forMenu">
                <?= $edit ? '✎ Modification en cours' : '+ Ajouter un menu' ?>
            </button>
        </div>
        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
        <?php if (isset($_GET['ok'])): ?><div class="alert alert-info">Menu <?= htmlspecialchars($_GET['ok']) ?>.</div><?php endif; ?>
        
        <div class="collapse <?= $edit ? 'show' : '' ?>" id="forMenu">
            <div class="card">
                <div class="card-header fw-bold"><?= $edit ? 'Modifier : ' .htmlspecialchars($edit['nom']) : 'Nouveau menu' ?></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $edit ? $edit['id'] : '' ?>">
                        <div class="row g-3">
                             <div class="col-md-4">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="nom" class="form-control" required value="<?= $edit ? htmlspecialchars($edit['nom']) : '' ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Catégorie *</label>
                                <input type="text" name="categorie" class="form-control" list="cats_list" required value="<?= $edit ? htmlspecialchars($edit['categorie']) : '' ?>">
                                <datalist id="cats_list"><?php foreach($cats as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?php endforeach; ?></datalist>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Prix (€) *</label>
                                <input type="text" name="prix" class="form-control" required value="<?= $edit ? $edit['prix'] : '' ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end pb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="disponible" id="dispo" <?= (!$edit || $edit['disponible']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="dispo">Disponible à la vente</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"><?= $edit ? htmlspecialchars($edit['description']) : '' ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">URL image <small class="text-muted">(optionnel)</small></label>
                                <input type="url" name="image_url" class="form-control" placeholder="https://..." value="<?= $edit ? htmlspecialchars($edit['image_url']??'') : '' ?>">
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><?= $edit ? 'Mettre à jour' : 'Ajouter' ?></button>
                            <?php if ($edit): ?><a href="menus.php" class="btn btn-secondary">Annuler</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div> <!-- card -->
        </div> <!-- collapse -->
    
        <?php foreach ($by_cat as $cat => $items): ?>
        <h5 class="mt-4 mb-2 border-bottom pb-1"><?= htmlspecialchars($cat) ?> <small class="text-muted fw-normal fs-6">(<?= count($items) ?> article<?= count($items)>1?'s':'' ?>)</small></h5>
        <div class="card mb-3">
            <div class="card-body p-0">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Nom</th><th>Description</th><th>Prix</th><th>Statut</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $m): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($m['nom']) ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($m['description']) ?></td>
                            <td class="fw-bold"><?= number_format($m['prix'],2,',',' ') ?> €</td>
                            <td>
                                <a href="?toggle=<?= $m['id'] ?>" class="badge <?= $m['disponible']?'bg-success':'bg-secondary' ?> text-decoration-none">
                                    <?= $m['disponible']?'✓ Disponible':'✗ Indisponible' ?>
                                </a>
                            </td>
                            <td>
                                <a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Supprimer ce menu ?')">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>  
            </div>
        </div> <!-- card -->
        <?php endforeach; ?>
    </div> <!-- co,ntainer-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>