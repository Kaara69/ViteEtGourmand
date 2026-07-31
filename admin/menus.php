<?php 
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../repositories/MenuRepository.php';
require_once __DIR__ . '/../services/MenuService.php';

$menuRepository = new MenuRepository($pdo);
$menuService = new MenuService($menuRepository);

checkAdmin();

$active_page = 'menus';

// Suppresion menu
$msg = '';
$msg_err = '';
if (isset($_GET['delete'])) {
    $menuRepository->delete((int)$_GET['delete']);
    header('Location: menus.php?ok=supprimé'); 
    exit; 
}

// Dispo
if (isset($_GET['toggle'])) {
    $menuRepository->toggleAvailability((int)$_GET['toggle']);
    header('Location: menus.php'); 
    exit;
}

// formulaire soumis (POST ajout/modif)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$msg, $msg_err] = $menuService->saveMenu($_POST, $_FILES);
}

// édition (pré-rempli formulaire)
$edit = null;

if (isset($_GET['edit'])) {
    $edit = $menuRepository->findById((int)$_GET['edit']);
}

// données affichage
$cats  = $menuRepository->getCategories();
$menus = $menuRepository->getAll();

// regroupement par catérgorie (tableau)
$by_cat = [];
foreach ($menus as $m) {
    $by_cat[$m['categorie']][] = $m;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Menus – Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/espace.css">
</head>
<body class="bg-light">

    <?php include __DIR__ . '/../includes/partials/admin_nav.php'; ?>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Gestion des menus</h4>
            <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#formMenu">
                <?= $edit ? 'Modification en cours' : '+ Ajouter un menu' ?>
            </button>
        </div> <!-- dflex -->
        <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
        <?php if (!empty($msg_err)): ?><div class="alert alert-danger"><?= htmlspecialchars($msg_err) ?></div><?php endif; ?>
        <?php if (isset($_GET['ok'])): ?><div class="alert alert-info">Menu <?= htmlspecialchars($_GET['ok']) ?>.</div><?php endif; ?>
        
        <div class="collapse <?= $edit ? 'show' : '' ?> mb-4" id="formMenu">
            <div class="card">
                <div class="card-header fw-bold">
                    <form method="post" enctype="multipart/form-data">
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
                                <label class="form-label fw-semibold">Photo du menu</label>
                                <?php if ($edit && !empty($edit['image_url'])): ?>
                                <div class="mb-2 d-flex align-items-center gap-3">
                                    <?php $preview = $edit['image_url'];
                                            if (str_starts_with($preview, 'assets/uploads/')) $preview = '../' . $preview; ?>
                                    <img src="<?= htmlspecialchars($preview) ?>" alt="Photo actuelle"
                                        style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:2px solid var(--gold);">
                                    <span class="text-muted small">Photo actuelle</span>
                                </div>
                                <?php endif; ?>
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted mb-1">📁 Uploader un fichier (JPG, PNG, WEBP — max 3 Mo)</label>
                                        <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end justify-content-center pb-2">
                                        <span class="text-muted small fw-bold">OU</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">🔗 URL externe (Unsplash, etc.)</label>
                                        <input type="text" name="image_url" class="form-control" placeholder="https://images.unsplash.com/..."
                                        value="<?= $edit ? htmlspecialchars($edit['image_url']??'') : '' ?>">
                                    </div>
                                </div>
                                <div class="form-text">Si vous uploadez un fichier, il sera prioritaire sur l'URL. Laissez les deux champs vides pour conserver la photo actuelle.</div>
                                    <?php if (!empty($msg_err)): ?>
                                    <div class="alert alert-danger mt-2 py-2"><?= htmlspecialchars($msg_err) ?></div>
                                    <?php endif; ?>
                            </div> <!-- col-12 -->
                        </div> <!-- row -->
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><?= $edit ? 'Mettre à jour' : 'Ajouter' ?></button>
                            <?php if ($edit): ?><a href="menus.php" class="btn btn-secondary">Annuler</a><?php endif; ?>
                        </div>
                    </form>
                </div> <!-- card-header -->
            </div> <!-- card -->
        </div> <!-- collapqse -->
        <?php foreach ($by_cat as $cat => $items): ?>
        <h5 class="mt-4 mb-2 border-bottom pb-1"><?= htmlspecialchars($cat) ?> <small class="text-muted fw-normal fs-6">(<?= count($items) ?> article<?= count($items)>1?'s':'' ?>)</small></h5>
        <div class="card mb-3">
            <div class="card-body p-0">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
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
        </div>
        <?php endforeach; ?>
    </div> <!-- container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>