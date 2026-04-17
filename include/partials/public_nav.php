<?php
$_active = $active_page ?? '';
$_logged = isset($_SESSION['user_id']);
$_role   = $_SESSION['role'] ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-main sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= strpos($_SERVER['PHP_SELF'],'/user/')!==false||strpos($_SERVER['PHP_SELF'],'/admin/')!==false||strpos($_SERVER['PHP_SELF'],'/employee/')!==false ? '../index.php' : 'index.php' ?>"> Vite &amp; Gourmand></a>
        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#pubNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="pubNav">
            <ul class="navbar-nav mx-auto gap-lg-2">
                <?php $base = (strpos($_SERVER['PHP_SELF'],'/user/')!==false||strpos($_SERVER['PHP_SELF'],'/admin/')!==false||strpos($_SERVER['PHP_SELF'],'/employee/')!==false) ? '../' : ''; ?>
                <li class="nav-item"><a class="nav-link <?= $_active==='accueil'?'active':'' ?>" href="<?= $base ?>index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link <?= $_active==='menus'?'active':'' ?>"   href="<?= $base ?>menus.php">Nos menus</a></li>
                <li class="nav-item"><a class="nav-link <?= $_active==='contact'?'active':'' ?>" href="<?= $base ?>contact.php">Contact</a></li>
            </ul>
            <div class="d-flex gap-2 mt-2 mt-lg-0">
                <?php if ($_logged): ?>
                    <?php if ($_role==='admin'): ?>
                        <a href="<?= $base ?>admin/dashboard.php"    class="btn btn-gold btn-sm">Dashboard admin</a>
                <?php elseif ($_role==='employee'): ?>
                    <a href="<?= $base ?>employee/dashboard.php" class="btn btn-gold btn-sm">Espace employé</a>
                <?php else: ?>
                    <a href="<?= $base ?>user/dashboard.php"     class="btn btn-gold btn-sm">Mon espace</a>
                <?php endif; ?>
                <?php else: ?>
                    <a href="<?= $base ?>login.php"    class="btn btn-outline-gold btn-sm">Connexion</a>
                    <a href="<?= $base ?>register.php" class="btn btn-gold btn-sm">S'inscrire</a>
                <?php endif; ?>
            </div>
        </div> <!-- collapse-->
    </div><!-- container-->
</nav>