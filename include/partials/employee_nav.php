<?php
$_active = $active_page ?? '';
$_pc = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en attente'")->fetchColumn();
$_pa = $pdo->query("SELECT COUNT(*) FROM avis WHERE statut='en attente'")->fetchColumn();
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background:#2D4A3E;">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php">🍽️ Espace Employé</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#empNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="empNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?= $_active==='dashboard'?'active':'' ?>" href="dashboard.php">Tableau de bord</a></li>
        <li class="nav-item"><a class="nav-link <?= $_active==='menus'?'active':'' ?>"     href="menus.php">Menus</a></li>
        <li class="nav-item">
          <a class="nav-link <?= $_active==='orders'?'active':'' ?>" href="orders.php">
            Commandes <?php if($_pc>0): ?><span class="badge bg-warning text-dark"><?= $_pc ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $_active==='reviews'?'active':'' ?>" href="reviews.php">
            Avis <?php if($_pa>0): ?><span class="badge bg-danger"><?= $_pa ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link <?= $_active==='schedules'?'active':'' ?>" href="schedules.php">Horaires</a></li>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item"><span class="nav-link text-muted small">👤 <?= htmlspecialchars($_SESSION['nom']) ?></span></li>
        <li class="nav-item"><a class="nav-link text-warning" href="../index.php">← Site</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Déconnexion</a></li>
      </ul>
    </div>
  </div>
</nav>
