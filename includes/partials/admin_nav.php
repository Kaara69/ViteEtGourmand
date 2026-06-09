<?php
$active = $active_page ??  '';

// rôle + badge

$is_admin = ($_SESSION['role'] === 'admin');
$pending_avis = $pdo->query("SELECT COUNT(*) FROM avis WHERE statut='en attente'")->fetchColumn();
$pending_cmd  = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en attente'")->fetchColumn();
?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background:#1C1510">
    <div class="container-fluid">
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="navbar-brand fw-bold">Administration</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">

            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $_active==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>admin/dashboard.php">Tableau de bord</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page==='menus' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/menus.php">Menus</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page==='orders' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/orders.php">Commandes 
                    <?php if($pending_cmd > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $pending_cmd ?></span>
                    <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page==='reviews' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/reviews.php">Avis 
                    <?php if($pending_avis > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pending_avis ?></span>
                    <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page==='schedules' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/schedules.php">Horaires</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page==='stats' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/stats.php">📊 Stats</a>
                </li>
                <?php if($is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active_page==='employees' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/employees.php">👥 Employés</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item" ><span class="nav-link small" style="color:#fff;">👤 <a class="text-warning text-decoration-none" href="<?=BASE_URL ?>admin/dashboard.php"><?= htmlspecialchars($_SESSION['nom']) ?></a></span></li>
                <li class="nav-item"><a class="nav-link text-warning" href="<?= BASE_URL ?>index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="<?= BASE_URL ?>logout.php">Déconnexion</a></li>
            </ul>
        </div> <!-- collapse -->
    </div> <!-- container -->
</nav>