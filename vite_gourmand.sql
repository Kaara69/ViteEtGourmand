-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3307
-- Généré le : dim. 12 avr. 2026 à 16:55
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

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
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nom` varchar(150) NOT NULL,
  `contenu` text NOT NULL,
  `note` tinyint(1) DEFAULT 5,
  `statut` varchar(50) DEFAULT 'en attente',
  `created_at` datetime DEFAULT current_timestamp()
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
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statut` varchar(50) DEFAULT 'en attente',
  `notes` text DEFAULT NULL,
  `nb_personnes` int(11) DEFAULT 1,
  `adresse_livraison` text DEFAULT '',
  `km_livraison` decimal(8,2) DEFAULT 0.00,
  `frais_livraison` decimal(8,2) DEFAULT 5.00,
  `remise` decimal(8,2) DEFAULT 0.00,
  `date_evenement` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commande_items`
--

CREATE TABLE `commande_items` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `nom_menu` varchar(200) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `horaires`
--

CREATE TABLE `horaires` (
  `id` int(11) NOT NULL,
  `jour` varchar(20) NOT NULL,
  `heure_ouverture` varchar(5) DEFAULT '09:00',
  `heure_fermeture` varchar(5) DEFAULT '19:00',
  `ferme` tinyint(1) DEFAULT 0
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
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `categorie` varchar(100) NOT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `image_url` text DEFAULT '',
  `allergenes` text DEFAULT '',
  `regime` varchar(200) DEFAULT '',
  `personnes_min` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menus`
--

INSERT INTO `menus` (`id`, `nom`, `description`, `prix`, `categorie`, `disponible`, `image_url`, `allergenes`, `regime`, `personnes_min`, `created_at`) VALUES
(1, 'Formule Gourmande', 'Velouté de butternut au lait de coco, suivi d\'un confit de canard aux pommes sarladaises et haricots verts, dessert du jour.', 19.90, 'Menu Classique', 1, 'uploads/menu_69b52a839e722.jpg', 'gluten,lactose,sulfites', '', 1, '2026-03-14 08:23:08'),
(2, 'Menu Vert & Saveurs', 'Gaspacho de tomates anciennes, curry de pois chiches aux légumes du soleil et riz basmati, mousse de mangue à la noix de coco.', 17.50, 'Menu Classique', 1, 'uploads/menu_69b52aa3959e7.jpg', '', 'vegan,vegetarien,sans_gluten', 1, '2026-03-14 08:32:41'),
(3, 'Réveillon Tradition', 'Velouté de châtaignes au foie gras poêlé, pintade farcie aux marrons et cèpes, gratin dauphinois, bûche pralinée maison.', 245.00, 'Menu Noël', 1, 'uploads/menu_69b52d5eca498.jpg', 'gluten,lactose,oeufs,sulfites', '', 10, '2026-03-14 08:50:26'),
(4, 'Noël Végétal', 'Velouté de panais à la truffe, risotto aux champignons des bois et parmesan, bûche au chocolat noir et fruits rouges.', 185.00, 'Menu Noël', 1, 'uploads/menu_69b52d507c3d3.jpg', 'gluten,lactose,oeufs', 'vegetarien', 15, '2026-03-14 08:56:47'),
(5, 'Agneau de Pâques', 'Assiette de saumon gravlax, gigot d\'agneau de 7 heures et gratin dauphinois, charlotte aux fraises et rhubarbe.', 220.00, 'Menu Pâques', 1, 'uploads/menu_69b52d6c24d90.jpg', 'gluten,lactose,oeufs,poisson', '', 10, '2026-03-14 09:02:56'),
(6, 'Pâques Printanier', 'Tartare de betterave et avocat, tajine de légumes nouveaux aux épices douces et semoule, salade d\'agrumes à la menthe fraîche.', 165.00, 'Menu Pâques', 1, 'uploads/menu_69b52d76a304b.jpg', 'gluten,sésame', 'vegan,vegetarien', 10, '2026-03-14 09:08:58'),
(7, 'Cocktail Dinatoire Mariage', '30 pièces par personne : verrines de tartare de saumon à l\'aneth, bouchées de foie gras sur pain d\'épices, mini-brochettes de bœuf sauce béarnaise, verrines de gaspacho tomate-basilic, tartelettes au chèvre et figues, macarons salés saumon-avocat, mini-éclairs au chocolat et caramel beurre salé.', 480.00, 'Menu Mariage', 1, 'uploads/menu_69b52d40541d2.jpg', 'gluten,lactose,oeufs,poisson,sulfites', '', 20, '2026-03-14 09:23:11'),
(8, 'Banquet Élégance', 'Amuse-bouches variés, foie gras mi-cuit sur brioche toastée, filet de boeuf sauce périgueux et légumes glacés, pièce montée choux.', 750.00, 'Menu Mariage', 1, 'uploads/menu_69b52d0dbe427.jpg', 'gluten,lactose,oeufs,sulfites', '', 20, '2026-03-14 09:28:26'),
(9, 'Banquet Mariage Prestige', '7 services : amuse-bouches au homard et caviar, velouté de truffe noire, tartare de Saint-Jacques en gelée au champagne, sorbet citron-basilic, filet de bœuf Rossini sauce Périgueux et légumes confits, plateau de fromages affinés, pièce montée personnalisée et mignardises.', 1450.00, 'Menu Mariage', 1, 'uploads/menu_69b52d24bd933.jpg', 'gluten,lactose,oeufs,sulfites,fruits_a_coque,poisson,mollusques,crustaces', '', 30, '2026-03-14 09:34:49');

-- --------------------------------------------------------

--
-- Structure de la table `nosql_documents`
--

CREATE TABLE `nosql_documents` (
  `id` int(11) NOT NULL,
  `collection` varchar(100) NOT NULL,
  `doc_id` varchar(40) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stockage orienté document (NoSQL-like)';

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee','client') NOT NULL DEFAULT 'client',
  `prenom` varchar(100) DEFAULT '',
  `telephone` varchar(30) DEFAULT '',
  `adresse` text DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `password`, `role`, `prenom`, `telephone`, `adresse`, `created_at`) VALUES
(1, 'Silva', 'admin@vitegourmand.fr', '$2y$10$MrpUI3MO.b0ZLFml3G9EWuoHpX/AD1x3OFhRcXQA3TdC8sFklrWiC', 'admin', 'Julie', '', '7 rue des Saveurs, 33000 Bordeaux', '2026-03-14 10:02:54'),
(2, 'Lopez', 'employe@vitegourmand.fr', '$2y$10$OYpqZ4InkPQFlVBAcgC8Zek3cAZPV4P3bc80UqDqelStBQAkbJuiq', 'employee', 'Gabrielle', '', '4 allée des Pins, 33300 Bordeaux', '2026-03-14 10:13:22'),
(3, 'Lefebvre', 'marie@client.fr', '$2y$10$toQjDgyVkwCJS7PVRafD5.mt53eA2OwIpNcnQ9WrcqBrDIt6uVhBe', 'client', 'Marie', '', '8 cours du Chapeau Rouge, 33000 Bordeaux', '2026-03-14 10:21:25'),
(4, 'Martin', 'pierre@client.fr', '$2y$10$F8Rldf0Q1BUCtugi9wQ22ePj43FLFmfO1A3/1DoiT6hGcPEi7REMy', 'client', 'Pierre', '', '15 rue de la Paix, 33160 Saint-Médard-en-Jalles', '2026-03-14 10:29:59');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commande_items`
--
ALTER TABLE `commande_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `horaires`
--
ALTER TABLE `horaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `nosql_documents`
--
ALTER TABLE `nosql_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
