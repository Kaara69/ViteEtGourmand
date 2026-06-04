<?php
$active = $active_page ?? '';
?>

<nav class="navbar navbar-expand-lg navbar-dark" style="background:#1C1510;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>index.php">Vite &amp; Gourmand</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="userNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $active==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>user/dashboard.php">Mon espace</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active==='menu'?'active':'' ?>" href="<?= BASE_URL ?>user/menu.php">Commander</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active==='orders'?'active':'' ?>" href="<?= BASE_URL ?>user/orders.php">Mes commandes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active==='profile'?'active':'' ?>" href="<?= BASE_URL ?>user/profile.php">Mon profil</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item" ><span class="nav-link small" style="color:#fff;">👤 <a class="text-warning text-decoration-none" href="<?=BASE_URL ?>user/profile.php"><?= htmlspecialchars($_SESSION['nom']) ?></a></span></li>
                    <li class="nav-item"><a class="nav-link text-warning" href="<?= BASE_URL ?>index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="<?= BASE_URL ?>logout.php">Déconnexion</a></li>
                </ul>
            </div>
    </div>
</nav>
