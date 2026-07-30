-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : db
-- Généré le : jeu. 30 juil. 2026 à 12:55
-- Version du serveur : 8.0.46
-- Version de PHP : 8.3.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `vite_gourmand`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `nom` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_general_ci NOT NULL,
  `note` tinyint(1) DEFAULT '5',
  `statut` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'en attente',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id`, `user_id`, `nom`, `contenu`, `note`, `statut`, `created_at`) VALUES
(1, NULL, 'Sophie M.', 'Réveillon de Noël absolument parfait. Le foie gras et la pintade farcie ont régalé toute la famille !', 5, 'approuvé', '2026-04-10 11:45:12'),
(2, NULL, 'Thomas B.', 'Menu mariage prestige pour 30 personnes — chaque service était digne d\'un grand restaurant.', 5, 'approuvé', '2026-04-10 12:14:29'),
(3, NULL, 'Isabelle R.', 'Brunch de Pâques commandé pour 10 personnes. Fraîcheur, qualité, générosité.', 4, 'approuvé', '2026-04-10 12:28:34'),
(4, NULL, 'Marc L.', 'Plateau gourmand pour notre apéro d\'anniversaire : la charcuterie du Périgord était exceptionnelle.', 5, 'approuvé', '2026-04-10 12:38:56'),
(5, NULL, 'Nathalie P.', 'Cocktail dinatoire mariage de ma fille : 20 personnes ravies, présentation magnifique.', 5, 'approuvé', '2026-04-10 12:45:02'),
(6, NULL, 'David K.', 'Commande régulière pour nos déjeuners d\'équipe. Toujours ponctuel, toujours délicieux.', 4, 'approuvé', '2026-04-10 12:55:25');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statut` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'en attente',
  `notes` text COLLATE utf8mb4_general_ci,
  `nb_personnes` int DEFAULT '1',
  `adresse_livraison` text COLLATE utf8mb4_general_ci,
  `km_livraison` decimal(8,2) DEFAULT '0.00',
  `frais_livraison` decimal(8,2) DEFAULT '5.00',
  `remise` decimal(8,2) DEFAULT '0.00',
  `date_evenement` date DEFAULT NULL,
  `heure_evenement` time DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `user_id`, `total`, `statut`, `notes`, `nb_personnes`, `adresse_livraison`, `km_livraison`, `frais_livraison`, `remise`, `date_evenement`, `heure_evenement`, `created_at`) VALUES
(5, 3, 2615.06, 'livré', '', 35, '8, Cours du Chapeau Rouge, Bordeaux, Port de la Lune, Bordeaux Centre, Bordeaux, Gironde, Nouvelle-Aquitaine, France métropolitaine, 33000, France', 0.12, 5.06, 290.00, '2026-06-05', NULL, '2026-05-27 21:48:18'),
(9, 4, 25.87, 'en attente', '', 1, 'Beauté 33, 13, Rue des Poilus, Le Bourg, Pessac, Bordeaux, Gironde, Nouvelle-Aquitaine, France métropolitaine, 33600, France', 6.24, 8.37, 0.00, NULL, NULL, '2026-06-11 18:38:32'),
(12, 4, 174.87, 'en attente', '', 20, 'Beauté 33, 13, Rue des Poilus, Le Bourg, Pessac, Bordeaux, Gironde, Nouvelle-Aquitaine, France métropolitaine, 33600, France', 6.24, 8.37, 18.50, NULL, NULL, '2026-06-11 18:51:16'),
(13, 3, 225.56, 'accepté', '', 15, '8, Cours du Chapeau Rouge, Bordeaux, Port de la Lune, Bordeaux Centre, Bordeaux, Gironde, Nouvelle-Aquitaine, France métropolitaine, 33000, France', 0.12, 5.06, 24.50, NULL, NULL, '2026-06-11 21:05:46'),
(14, 4, 228.87, 'annulé', '', 15, 'Beauté 33, 13, Rue des Poilus, Le Bourg, Pessac, Bordeaux, Gironde, Nouvelle-Aquitaine, France métropolitaine, 33600, France', 6.24, 8.37, 24.50, NULL, NULL, '2026-06-12 09:27:54'),
(16, 4, 339.12, 'accepté', '', 15, 'Beauté 33, 13, Rue des Poilus, Le Bourg, Pessac, Bordeaux, Gironde, Nouvelle-Aquitaine, France métropolitaine, 33600, France', 6.24, 8.37, 36.75, NULL, NULL, '2026-06-12 10:04:39'),
(17, 3, 162.56, 'en attente', '', 10, '8, Cours du Chapeau Rouge, Bordeaux, Port de la Lune, Bordeaux Centre, Bordeaux, Gironde, Nouvelle-Aquitaine, France métropolitaine, 33000, France', 0.12, 5.06, 17.50, '2026-08-04', '19:15:00', '2026-07-30 12:53:07');

-- --------------------------------------------------------

--
-- Structure de la table `commande_items`
--

CREATE TABLE `commande_items` (
  `id` int NOT NULL,
  `commande_id` int NOT NULL,
  `menu_id` int NOT NULL,
  `nom_menu` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  `prix_unitaire` decimal(10,2) NOT NULL,
  `personnes_min` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande_items`
--

INSERT INTO `commande_items` (`id`, `commande_id`, `menu_id`, `nom_menu`, `quantite`, `prix_unitaire`, `personnes_min`) VALUES
(5, 5, 9, 'Banquet Mariage Prestige', 2, 1450.00, 1),
(10, 9, 2, 'Menu Vert & Saveurs', 1, 87.50, 1),
(13, 12, 4, 'Noël Végétal', 1, 185.00, 1),
(14, 13, 3, 'Réveillon Tradition', 1, 245.00, 1),
(15, 14, 3, 'Réveillon Tradition', 1, 245.00, 1),
(17, 16, 3, 'Réveillon Tradition', 1, 245.00, 10),
(18, 17, 2, 'Menu Vert & Saveurs', 1, 87.50, 5);

-- --------------------------------------------------------

--
-- Structure de la table `horaires`
--

CREATE TABLE `horaires` (
  `id` int NOT NULL,
  `jour` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `heure_ouverture` varchar(5) COLLATE utf8mb4_general_ci DEFAULT '09:00',
  `heure_fermeture` varchar(5) COLLATE utf8mb4_general_ci DEFAULT '19:00',
  `ferme` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `horaires`
--

INSERT INTO `horaires` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `ferme`) VALUES
(1, 'Lundi', '00:00', '00:00', 1),
(2, 'Mardi', '09:00', '19:00', 0),
(3, 'Mercredi', '09:00', '19:00', 0),
(4, 'Jeudi', '09:00', '19:00', 0),
(5, 'Vendredi', '09:00', '19:00', 0),
(6, 'Samedi', '09:00', '19:00', 0),
(7, 'Dimanche', '09:00', '19:00', 0);

-- --------------------------------------------------------

--
-- Structure de la table `menus`
--

CREATE TABLE `menus` (
  `id` int NOT NULL,
  `nom` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `prix` decimal(10,2) NOT NULL,
  `categorie` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `disponible` tinyint(1) DEFAULT '1',
  `image_url` text COLLATE utf8mb4_general_ci,
  `allergenes` text COLLATE utf8mb4_general_ci,
  `regime` varchar(200) COLLATE utf8mb4_general_ci DEFAULT '',
  `personnes_min` int DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menus`
--

INSERT INTO `menus` (`id`, `nom`, `description`, `prix`, `categorie`, `disponible`, `image_url`, `allergenes`, `regime`, `personnes_min`, `created_at`) VALUES
(1, 'Formule Gourmande', 'Velouté de butternut au lait de coco, suivi d\'un confit de canard aux pommes sarladaises et haricots verts, dessert du jour.', 99.50, 'Menu Classique', 1, 'assets/uploads/menu_69b52a839e722.jpg', 'gluten,lactose,sulfites', '', 5, '2026-03-14 08:23:08'),
(2, 'Menu Vert & Saveurs', 'Gaspacho de tomates anciennes, curry de pois chiches aux légumes du soleil et riz basmati, mousse de mangue à la noix de coco.', 87.50, 'Menu Classique', 1, 'assets/uploads/menu_69b52aa3959e7.jpg', '', 'vegan,vegetarien,sans_gluten', 5, '2026-03-14 08:32:41'),
(3, 'Réveillon Tradition', 'Velouté de châtaignes au foie gras poêlé, pintade farcie aux marrons et cèpes, gratin dauphinois, bûche pralinée maison.', 245.00, 'Menu Noël', 1, 'assets/uploads/menu_69b52d5eca498.jpg', 'gluten,lactose,oeufs,sulfites', '', 10, '2026-03-14 08:50:26'),
(4, 'Noël Végétal', 'Velouté de panais à la truffe, risotto aux champignons des bois et parmesan, bûche au chocolat noir et fruits rouges.', 185.00, 'Menu Noël', 1, 'assets/uploads/menu_69b52d507c3d3.jpg', 'gluten,lactose,oeufs', 'vegetarien', 10, '2026-03-14 08:56:47'),
(5, 'Agneau de Pâques', 'Assiette de saumon gravlax, gigot d\'agneau de 7 heures et gratin dauphinois, charlotte aux fraises et rhubarbe.', 220.00, 'Menu Pâques', 1, 'assets/uploads/menu_69b52d6c24d90.jpg', 'gluten,lactose,oeufs,poisson', '', 10, '2026-03-14 09:02:56'),
(6, 'Pâques Printanier', 'Tartare de betterave et avocat, tajine de légumes nouveaux aux épices douces et semoule, salade d\'agrumes à la menthe fraîche.', 165.00, 'Menu Pâques', 1, 'assets/uploads/menu_69b52d76a304b.jpg', 'gluten,sésame', 'vegan,vegetarien', 10, '2026-03-14 09:08:58'),
(7, 'Cocktail Dinatoire Mariage', '30 pièces par personne : verrines de tartare de saumon à l\'aneth, bouchées de foie gras sur pain d\'épices, mini-brochettes de bœuf sauce béarnaise, verrines de gaspacho tomate-basilic, tartelettes au chèvre et figues, macarons salés saumon-avocat, mini-éclairs au chocolat et caramel beurre salé.', 480.00, 'Menu Mariage', 1, 'assets/uploads/menu_69b52d40541d2.jpg', 'gluten,lactose,oeufs,poisson,sulfites', '', 25, '2026-03-14 09:23:11'),
(8, 'Banquet Élégance', 'Amuse-bouches variés, foie gras mi-cuit sur brioche toastée, filet de boeuf sauce périgueux et légumes glacés, pièce montée choux.', 750.00, 'Menu Mariage', 1, 'assets/uploads/menu_69b52d0dbe427.jpg', 'gluten,lactose,oeufs,sulfites', '', 25, '2026-03-14 09:28:26'),
(9, 'Banquet Mariage Prestige', '7 services : amuse-bouches au homard et caviar, velouté de truffe noire, tartare de Saint-Jacques en gelée au champagne, sorbet citron-basilic, filet de bœuf Rossini sauce Périgueux et légumes confits, plateau de fromages affinés, pièce montée personnalisée et mignardises.', 1350.00, 'Menu Mariage', 1, 'assets/uploads/menu_69b52d24bd933.jpg', 'gluten,lactose,oeufs,sulfites,fruits_a_coque,poisson,mollusques,crustaces', '', 25, '2026-03-14 09:34:49');

-- --------------------------------------------------------

--
-- Structure de la table `nosql_documents`
--

CREATE TABLE `nosql_documents` (
  `id` int NOT NULL,
  `collection` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `doc_id` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `nosql_documents`
--

INSERT INTO `nosql_documents` (`id`, `collection`, `doc_id`, `data`, `created_at`, `updated_at`) VALUES
(16, 'stats_menu', '1aae7f83b4575798d03f10bbb95924d9', '{\"menu_id\":9,\"nom_menu\":\"Banquet Mariage Prestige\",\"categorie\":\"Menu Mariage\",\"nb_commandes\":2,\"chiffre_affaires\":2900,\"prix_moyen\":1450,\"nb_commandes_distinctes\":1,\"premiere_commande\":\"2026-05-27 21:48:18\",\"derniere_commande\":\"2026-05-27 21:48:18\",\"_created_at\":\"2026-06-11 17:54:52\",\"_id\":\"1aae7f83b4575798d03f10bbb95924d9\"}', '2026-06-11 17:54:52', '2026-06-11 17:54:52'),
(17, 'stats_daily', 'd952806aedd611f482d9f936c948cc15', '{\"menu_id\":9,\"nom_menu\":\"Banquet Mariage Prestige\",\"jour\":\"2026-05-27\",\"nb_commandes\":2,\"chiffre_affaires\":2900,\"_created_at\":\"2026-06-11 17:54:52\",\"_id\":\"d952806aedd611f482d9f936c948cc15\"}', '2026-06-11 17:54:52', '2026-06-11 17:54:52'),
(18, 'stats_menu', 'cb18dfc84976a3a9271338c26f19f88a', '{\"menu_id\":2,\"nom_menu\":\"Menu Vert & Saveurs\",\"nb_commandes\":3,\"chiffre_affaires\":52.5,\"prix_moyen\":17.5,\"premiere_commande\":\"2026-06-11 18:38:27\",\"derniere_commande\":\"2026-06-11 18:38:51\",\"_created_at\":\"2026-06-11 18:38:27\",\"_updated_at\":\"2026-06-11 18:38:51\"}', '2026-06-11 18:38:27', '2026-06-11 18:38:51'),
(19, 'stats_daily', '3f20562dc8984ab63aa784de9abb59d4', '{\"menu_id\":2,\"nom_menu\":\"Menu Vert & Saveurs\",\"jour\":\"2026-06-11\",\"nb_commandes\":3,\"chiffre_affaires\":52.5,\"_created_at\":\"2026-06-11 18:38:27\",\"_updated_at\":\"2026-06-11 18:38:51\"}', '2026-06-11 18:38:27', '2026-06-11 18:38:51'),
(20, 'stats_menu', 'dd621202bc16c3794257dcd0cf5aa863', '{\"menu_id\":4,\"nom_menu\":\"Noël Végétal\",\"nb_commandes\":3,\"chiffre_affaires\":555,\"prix_moyen\":185,\"premiere_commande\":\"2026-06-11 18:50:27\",\"derniere_commande\":\"2026-06-12 09:41:03\",\"_created_at\":\"2026-06-11 18:50:27\",\"_updated_at\":\"2026-06-12 09:41:03\"}', '2026-06-11 18:50:27', '2026-06-12 09:41:03'),
(21, 'stats_daily', 'efb4118722b76c321c35069d48f16f27', '{\"menu_id\":4,\"nom_menu\":\"Noël Végétal\",\"jour\":\"2026-06-11\",\"nb_commandes\":2,\"chiffre_affaires\":370,\"_created_at\":\"2026-06-11 18:50:27\",\"_updated_at\":\"2026-06-11 18:51:16\"}', '2026-06-11 18:50:27', '2026-06-11 18:51:16'),
(22, 'stats_menu', '5423495fa92f80be772c04d46cac854f', '{\"menu_id\":3,\"nom_menu\":\"Réveillon Tradition\",\"nb_commandes\":3,\"chiffre_affaires\":735,\"prix_moyen\":245,\"premiere_commande\":\"2026-06-11 21:05:46\",\"derniere_commande\":\"2026-06-12 10:04:39\",\"_created_at\":\"2026-06-11 21:05:46\",\"_updated_at\":\"2026-06-12 10:04:39\"}', '2026-06-11 21:05:46', '2026-06-12 10:04:39'),
(23, 'stats_daily', '3acd6d15459a44218cf9224effbc3b49', '{\"menu_id\":3,\"nom_menu\":\"Réveillon Tradition\",\"jour\":\"2026-06-11\",\"nb_commandes\":1,\"chiffre_affaires\":245,\"_created_at\":\"2026-06-11 21:05:46\",\"_id\":\"3acd6d15459a44218cf9224effbc3b49\"}', '2026-06-11 21:05:46', '2026-06-11 21:05:46'),
(24, 'stats_daily', '173f6c1ccec60f7e39b2675dfbfaf77a', '{\"menu_id\":3,\"nom_menu\":\"Réveillon Tradition\",\"jour\":\"2026-06-12\",\"nb_commandes\":2,\"chiffre_affaires\":490,\"_created_at\":\"2026-06-12 09:27:54\",\"_updated_at\":\"2026-06-12 10:04:39\"}', '2026-06-12 09:27:54', '2026-06-12 10:04:39'),
(25, 'stats_daily', '35f19b30cfe879529d9da3f119ffeb6d', '{\"menu_id\":4,\"nom_menu\":\"Noël Végétal\",\"jour\":\"2026-06-12\",\"nb_commandes\":1,\"chiffre_affaires\":185,\"_created_at\":\"2026-06-12 09:41:03\",\"_id\":\"35f19b30cfe879529d9da3f119ffeb6d\"}', '2026-06-12 09:41:03', '2026-06-12 09:41:03');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nom` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','employee','client') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'client',
  `prenom` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telephone` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `password`, `role`, `prenom`, `telephone`, `adresse`, `created_at`) VALUES
(1, 'Silva', 'admin@vitegourmand.fr', '$2y$10$MrpUI3MO.b0ZLFml3G9EWuoHpX/AD1x3OFhRcXQA3TdC8sFklrWiC', 'admin', 'Julie', '', '7 rue des Saveurs, 33000 Bordeaux', '2026-03-14 10:02:54'),
(2, 'Lopez', 'employe@vitegourmand.fr', '$2y$10$OYpqZ4InkPQFlVBAcgC8Zek3cAZPV4P3bc80UqDqelStBQAkbJuiq', 'employee', 'Gabrielle', '', '4 allée des Pins, 33300 Bordeaux', '2026-03-14 10:13:22'),
(3, 'Lefebvre', 'marie@client.fr', '$2y$10$toQjDgyVkwCJS7PVRafD5.mt53eA2OwIpNcnQ9WrcqBrDIt6uVhBe', 'client', 'Marie', '', '8 cours du Chapeau Rouge, 33000 Bordeaux', '2026-03-14 10:21:25'),
(4, 'Martin', 'pierre@client.fr', '$2y$10$F8Rldf0Q1BUCtugi9wQ22ePj43FLFmfO1A3/1DoiT6hGcPEi7REMy', 'client', 'Pierre', '', '13 rue des Poilus, 33600 Pessac', '2026-03-14 10:29:59');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_avis` (`nom`,`contenu`(100)),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `commande_items`
--
ALTER TABLE `commande_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`);

--
-- Index pour la table `horaires`
--
ALTER TABLE `horaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jour` (`jour`);

--
-- Index pour la table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`),
  ADD UNIQUE KEY `uk_menu_nom` (`nom`);

--
-- Index pour la table `nosql_documents`
--
ALTER TABLE `nosql_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_col_doc` (`collection`,`doc_id`),
  ADD KEY `idx_collection` (`collection`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `commande_items`
--
ALTER TABLE `commande_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `horaires`
--
ALTER TABLE `horaires`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `nosql_documents`
--
ALTER TABLE `nosql_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `commande_items`
--
ALTER TABLE `commande_items`
  ADD CONSTRAINT `commande_items_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
