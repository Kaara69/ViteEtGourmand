<?php
$active = $active_page ?? '';
?>

<nav class="navbar navbar-expand-lg navbar-dark" style="background:#1C1510;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">Vite &amp; Gourmand</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="userNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $active==='dashboard'?'active':'' ?>" href="dashboard.php">Mon espace</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active==='menu'?'active':'' ?>" href="menu.php">Commander</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active==='orders'?'active':'' ?>" href="orders.php">Mes commandes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active==='profile'?'active':'' ?>" href="profile.php">Mon profil</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><span class="nav-link text-muted small">👤 <?= htmlspecialchars($_SESSION['nom']) ?></span></li>
                    <li class="nav-item"><a class="nav-link text-warning" href="../index.php">← Site</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Déconnexion</a></li>
                </ul>
            </div>
    </div>
</nav>