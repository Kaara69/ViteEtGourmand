<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Installation – Vite &amp; Gourmand</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="col-md-8 mx-auto">
    <div class="alert alert-success">
      <h4>✅ Installation réussie !</h4>
      <p class="mb-0 small">Base <code><?= DB_NAME ?></code> créée — tables relationnelles + table orientée document <code>nosql_documents</code>.</p>
    </div>
    <div class="card mb-3">
      <div class="card-header fw-bold">Comptes de test</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Rôle</th><th>Email</th><th>Mot de passe</th></tr></thead>
          <tbody>
            <tr><td><span class="badge bg-danger">Admin</span></td><td>admin@vitegourmand.fr</td><td><code>admin123</code></td></tr>
            <tr><td><span class="badge bg-success">Employé</span></td><td>employe@vitegourmand.fr</td><td><code>employe123</code></td></tr>
            <tr><td><span class="badge bg-primary">Client</span></td><td>marie@client.fr</td><td><code>client123</code></td></tr>
            <tr><td><span class="badge bg-secondary">Client</span></td><td>pierre@client.fr</td><td><code>client123</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="alert alert-warning small">
      ⚠️ Après installation, connectez-vous en admin → <strong>Statistiques</strong> → <strong>🔄 Resync NoSQL</strong>
    </div>
    <a href="index.php" class="btn btn-dark me-2">← Accueil</a>
    <a href="admin/stats.php" class="btn btn-warning">Statistiques →</a>
  </div>
</div>
</body>
</html>