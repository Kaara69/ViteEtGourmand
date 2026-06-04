<?php
$_active = $active_page ?? '';
$_pc = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en attente'")->fetchColumn();
$_pa = $pdo->query("SELECT COUNT(*) FROM avis WHERE statut='en attente'")->fetchColumn();
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background:#2D4A3E;">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>employee/dashboard.php">🍽️ Espace Employé</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#empNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="empNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?= $_active==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>employee/dashboard.php">Tableau de bord</a></li>
        <li class="nav-item"><a class="nav-link <?= $_active==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>employee/menus.php">Menu</a></li>
        <li class="nav-item">
          <a class="nav-link <?= $_active==='orders'?'active':'' ?>" href="<?= BASE_URL ?>employee/orders.php">
            Commandes <?php if($_pc>0): ?><span class="badge bg-warning text-dark"><?= $_pc ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $_active==='reviews'?'active':'' ?>" href="<?= BASE_URL ?>employee/reviews.php">
            Avis <?php if($_pa>0): ?><span class="badge bg-danger"><?= $_pa ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link <?= $_active==='schedules'?'active':'' ?>" href="<?= BASE_URL ?>employee/schedules.php">Horaires</a></li>
      </ul>
      <ul class="navbar-nav">
          <li class="nav-item" ><span class="nav-link small" style="color:#fff;">👤 <a class="text-warning text-decoration-none" href="<?=BASE_URL ?>employee/dashboard.php"><?= htmlspecialchars($_SESSION['nom']) ?></a></span></li>
          <li class="nav-item"><a class="nav-link text-warning" href="<?= BASE_URL ?>index.php">Accueil</a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="<?= BASE_URL ?>logout.php">Déconnexion</a></li>
      </ul>
    </div>
  </div>
</nav>
