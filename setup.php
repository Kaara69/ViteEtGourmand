<?php
require_once __DIR__ . '/config.php';


//  Connexion + création de la base si besoin
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Crée et utilise la base vite_gourmand si elle n’existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `vite_gourmand` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `vite_gourmand`");
} catch (PDOException $e) {
    die('Erreur MySQL : ' . $e->getMessage());
}



// 1. TABLE `users` 
$pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','employee','client') NOT NULL DEFAULT 'client',
    `prenom` VARCHAR(100) DEFAULT '',
    `telephone` VARCHAR(30) DEFAULT '',
    `adresse` TEXT DEFAULT '',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");


//  2. TABLE `menus`
$pdo->exec("CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `prix` DECIMAL(10,2) NOT NULL,
    `categorie` VARCHAR(100) NOT NULL,
    `disponible` TINYINT(1) DEFAULT 1,
    `image_url` TEXT DEFAULT '',
    `allergenes` TEXT DEFAULT '',
    `regime` VARCHAR(200) DEFAULT '',
    `personnes_min` INT(11) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");


//  3. TABLE `horaires`
$pdo->exec("CREATE TABLE IF NOT EXISTS `horaires` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `jour` VARCHAR(20) NOT NULL,
    `heure_ouverture` VARCHAR(5) DEFAULT '09:00',
    `heure_fermeture` VARCHAR(5) DEFAULT '19:00',
    `ferme` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `jour` (`jour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");


// 4. TABLE `commandes`
$pdo->exec("CREATE TABLE IF NOT EXISTS `commandes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `statut` VARCHAR(50) DEFAULT 'en attente',
    `notes` TEXT,
    `nb_personnes` INT(11) DEFAULT 1,
    `adresse_livraison` TEXT DEFAULT '',
    `km_livraison` DECIMAL(8,2) DEFAULT 0.00,
    `frais_livraison` DECIMAL(8,2) DEFAULT 5.00,
    `remise` DECIMAL(8,2) DEFAULT 0.00,
    `date_evenement` DATE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");


// 5. TABLE `commande_items` 
$pdo->exec("CREATE TABLE IF NOT EXISTS `commande_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `commande_id` INT(11) NOT NULL,
    `menu_id` INT(11) NOT NULL,
    `nom_menu` VARCHAR(200) NOT NULL,
    `quantite` INT(11) NOT NULL DEFAULT 1,
    `prix_unitaire` DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `commande_id` (`commande_id`),
    CONSTRAINT `commande_items_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");


//  6. TABLE `avis` 
$pdo->exec("CREATE TABLE IF NOT EXISTS `avis` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `nom` VARCHAR(150) NOT NULL,
    `contenu` TEXT NOT NULL,
    `note` TINYINT(1) DEFAULT 5,
    `statut` VARCHAR(50) DEFAULT 'en attente',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_avis` (`nom`,`contenu`(100)),
    KEY `user_id` (`user_id`),
    CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");


// 7. TABLE `nosql_documents` (NoSQLStore)
$pdo->exec("CREATE TABLE IF NOT EXISTS `nosql_documents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `collection` VARCHAR(100) NOT NULL,
    `doc_id` VARCHAR(40) NOT NULL,
    `data` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_col_doc` (`collection`,`doc_id`),
    KEY `idx_collection` (`collection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stockage orienté document (NoSQL-like)'");


// 8. Ajout de contraintes manquantes (si jamais la base est incomplète)
try {
    $pdo->exec("ALTER TABLE `menus` ADD UNIQUE KEY `uk_menu_nom` (`nom`)");
} catch (PDOException $e) { /* déjà présent */ }

// 9. Données de test `users` (admin, employé, clients)
$insUser = $pdo->prepare("
    INSERT IGNORE INTO `users`
    (nom, email, password, role, prenom, telephone, adresse)
    VALUES (?,?,?,?,?,?,?)
");

$insUser->execute(['Silva', 'admin@vitegourmand.fr', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 'Julie', '', '7 rue des Saveurs, 33000 Bordeaux']);
$insUser->execute(['Lopez', 'employe@vitegourmand.fr', password_hash('employe123', PASSWORD_DEFAULT), 'employee', 'Gabrielle', '', '4 allée des Pins, 33300 Bordeaux']);
$insUser->execute(['Lefebvre', 'marie@client.fr', password_hash('client123', PASSWORD_DEFAULT), 'client', 'Marie', '', '8 cours du Chapeau Rouge, 33000 Bordeaux']);
$insUser->execute(['Martin', 'pierre@client.fr', password_hash('client123', PASSWORD_DEFAULT), 'client', 'Pierre', '', '15 rue de la Paix, 33160 Saint-Médard-en-Jalles']);


// ── 10. Données de test `menus`
$menuItems = [
    ['Formule Gourmande', 'Velouté de butternut au lait de coco, suivi d\'un confit de canard aux pommes sarladaises et haricots verts, dessert du jour.', 19.90, 'Menu Classique', 1, 'uploads/menu_69b52a839e722.jpg', 'gluten,lactose,sulfites', '', 1],
    ['Menu Vert & Saveurs', 'Gaspacho de tomates anciennes, curry de pois chiches aux légumes du soleil et riz basmati, mousse de mangue à la noix de coco.', 17.50, 'Menu Classique', 1, 'uploads/menu_69b52aa3959e7.jpg', '', 'vegan,vegetarien,sans_gluten', 1],
    ['Réveillon Tradition', 'Velouté de châtaignes au foie gras poêlé, pintade farcie aux marrons et cèpes, gratin dauphinois, bûche pralinée maison.', 245.00, 'Menu Noël', 1, 'uploads/menu_69b52d5eca498.jpg', 'gluten,lactose,oeufs,sulfites', '', 10],
    ['Noël Végétal', 'Velouté de panais à la truffe, risotto aux champignons des bois et parmesan, bûche au chocolat noir et fruits rouges.', 185.00, 'Menu Noël', 1, 'uploads/menu_69b52d507c3d3.jpg', 'gluten,lactose,oeufs', 'vegetarien', 15],
    ['Agneau de Pâques', 'Assiette de saumon gravlax, gigot d\'agneau de 7 heures et gratin dauphinois, charlotte aux fraises et rhubarbe.', 220.00, 'Menu Pâques', 1, 'uploads/menu_69b52d6c24d90.jpg', 'gluten,lactose,oeufs,poisson', '', 10],
    ['Pâques Printanier', 'Tartare de betterave et avocat, tajine de légumes nouveaux aux épices douces et semoule, salade d\'agrumes à la menthe fraîche.', 165.00, 'Menu Pâques', 1, 'uploads/menu_69b52d76a304b.jpg', 'gluten,sésame', 'vegan,vegetarien', 10],
    ['Cocktail Dinatoire Mariage', '30 pièces par personne : verrines de tartare de saumon à l\'aneth, bouchées de foie gras sur pain d\'épices, mini-brochettes de bœuf sauce béarnaise, verrines de gaspacho tomate-basilic, tartelettes au chèvre et figues, macarons salés saumon-avocat, mini-éclairs au chocolat et caramel beurre salé.', 480.00, 'Menu Mariage', 1, 'uploads/menu_69b52d40541d2.jpg', 'gluten,lactose,oeufs,poisson,sulfites', '', 20],
    ['Banquet Élégance', 'Amuse-bouches variés, foie gras mi-cuit sur brioche toastée, filet de boeuf sauce périgueux et légumes glacés, pièce montée choux.', 750.00, 'Menu Mariage', 1, 'uploads/menu_69b52d0dbe427.jpg', 'gluten,lactose,oeufs,sulfites', '', 20],
    ['Banquet Mariage Prestige', '7 services : amuse-bouches au homard et caviar, velouté de truffe noire, tartare de Saint-Jacques en gelée au champagne, sorbet citron-basilic, filet de bœuf Rossini sauce Périgueux et légumes confits, plateau de fromages affinés, pièce montée personnalisée et mignardises.', 1450.00, 'Menu Mariage', 1, 'uploads/menu_69b52d24bd933.jpg', 'gluten,lactose,oeufs,sulfites,fruits_a_coque,poisson,mollusques,crustaces', '', 30],
];

$insMenu = $pdo->prepare("
    INSERT IGNORE INTO `menus`
    (nom, description, prix, categorie, disponible, image_url, allergenes, regime, personnes_min)
    VALUES (?,?,?,?,?,?,?,?,?)
");

foreach ($menuItems as $m) {
    $m[1] = $m[1] ?? ''; // évite notice
    $m[7] = $m[7] ?? ''; // régime
    $m[8] = $m[8] ?? 1;  // personnes_min
    $insMenu->execute($m);
}


// ── 11. Données de test `horaires` (Tout le SQL que tu as exporté) ──
$horaires = [
    ['Lundi', '00:00', '00:00', 1],
    ['Mardi', '09:00', '19:00', 0],
    ['Mercredi', '09:00', '19:00', 0],
    ['Jeudi', '09:00', '19:00', 0],
    ['Vendredi', '09:00', '19:00', 0],
    ['Samedi', '09:00', '19:00', 0],
    ['Dimanche', '09:00', '19:00', 0],
];

$insHoraire = $pdo->prepare("
    INSERT IGNORE INTO `horaires`
    (jour, heure_ouverture, heure_fermeture, ferme)
    VALUES (?,?,?,?)
");

foreach ($horaires as $h) {
    $insHoraire->execute($h);
}


// ── 12. Données de test `avis` ──────────────────────────────────
$avisItems = [
    ['Sophie M.', 'Réveillon de Noël absolument parfait. Le foie gras et la pintade farcie ont régalé toute la famille !', 5, 'approuvé'],
    ['Thomas B.', 'Menu mariage prestige pour 30 personnes — chaque service était digne d\'un grand restaurant.', 5, 'approuvé'],
    ['Isabelle R.', 'Brunch de Pâques commandé pour 10 personnes. Fraîcheur, qualité, générosité.', 4, 'approuvé'],
    ['Marc L.', 'Plateau gourmand pour notre apéro d\'anniversaire : la charcuterie du Périgord était exceptionnelle.', 5, 'approuvé'],
    ['Nathalie P.', 'Cocktail dinatoire mariage de ma fille : 20 personnes ravies, présentation magnifique.', 5, 'approuvé'],
    ['David K.', 'Commande régulière pour nos déjeuners d\'équipe. Toujours ponctuel, toujours délicieux.', 4, 'approuvé'],
];

$insAvis = $pdo->prepare("
    INSERT IGNORE INTO `avis`
    (nom, contenu, note, statut)
    VALUES (?,?,?,?)
");

foreach ($avisItems as $a) {
    $insAvis->execute($a);
}
?>

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