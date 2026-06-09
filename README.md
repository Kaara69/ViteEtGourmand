# Vite & Gourmand — Documentation technique

> Projet ECF — Application web de commande en ligne pour un traiteur bordelais.

---

## Sommaire

1. [Présentation](#1-présentation)
2. [Structure des fichiers](#2-structure-des-fichiers)
3. [Architecture bi-base](#3-architecture-bi-base)
4. [Schéma de base de données](#4-schéma-de-base-de-données)
5. [Fonctionnalités](#5-fonctionnalités)
6. [Prérequis](#6-prérequis)
7. [Installation locale (XAMPP)](#7-installation-locale-xampp)
8. [Déploiement](#8-déploiement)


## 1. Présentation

**Vite & Gourmand** est une application web pour un traiteur basé à Bordeaux.
Elle permet de consulter les menus, passer des commandes en ligne avec calcul de livraison
kilométrique, et suivre les commandes en temps réel.

L'administrateur peut consulter les commandes ainsi qu'un graphique de son CA depuis son tableau de bord.

| Rôle | Accès |
|------|-------|
| **Visiteur** | Accueil, carte des menus, contact |
| **Client** | Commander, suivre ses commandes, profil, avis |
| **Employé** | Commandes, menus, avis, horaires |
| **Admin** | Tout + gestion employés + statistiques |


## 2. Structure des fichiers

```
viteetgourmand/
├── config.php                  ← Credentials MySQL (à modifier en prod)
├── vite_gourmand.sql           ← Schéma + données à importer via phpMyAdmin
├── setup.php                   ← Installation automatique (1 seule fois)
│
├── index.php                   ← Accueil public
├── menus.php                   ← Carte des menus + filtres
├── contact.php                 ← Contact
├── login.php / register.php / logout.php
├── .htaccess                   ← désactive l’affichage des listes de fichiers et protège certains types de fichiers sensibles 
                                (comme .sql, .bak, etc) qui pourraient contenir des données de la base MySQL.
│
├── css/
│   ├── global.css              ← Variables, navbar, boutons (toutes les pages)
│   ├── public.css              ← Pages publiques
│   └── espace.css              ← Espaces connectés (panier, commandes, stats)
│
├── includes/
│   ├── db.php                  ← Connexion MySQL PDO
│   ├── auth.php                ← Helpers session, rôles, badges statut
│   ├── nosql_db.php            ← NoSQLStore (orienté document sur MySQL) + StatsSync
│   └── partials/               ← Navbars (public, user, admin, employee)
│
├── user/                       ← Espace client (dashboard, menu, orders, profile)
├── admin/                      ← Back-office admin (dashboard, menus, orders, reviews,
│                                  schedules, employees, stats)
├── employee/                   ← Back-office employé
└── api/
    └── order_status.php        ← API JSON pour le polling statut commandes
```
## 3. Architecture bi-base

L'application utilise **une seule base MySQL**, mais avec deux usages distincts, illustrant
le concept de base relationnelle et base orientée document :

```

MySQL
|── Tables relationnelles (données métier)
|   |── users, menus, horaires
|   |── commandes, commande_items
|   |── avis
|
|── Tables orientée document (donées analytiques)
    |── nosql_documents -> stocke des JSON par collection
        |── collection : "stats_menus (stats par menu)
        |── collection : "stats_daily (stats par jour)

```

La table `nosql_db` imite le fonctionnement d'une base NoSQL orientée documents (comme MongoDB) : 
chaque ligne contient un document JSON indépendant, sans schéma fixe.
La classe PHP `NoSQLStore` expose une API identique à MongoDB (`find`, `findOne`
`insertOne`, `updateOne`, `upsertOne`, `deleteMany`).

La classe `StatsSync` synchronise les données relationnelles vers la partie document
après chaque commande, et à la demande depuis le panneau admin.

---

## 4. Schéma de base de données

### Tables relationnelles

| Table | Description |
|-------|-------------|
| `users` | Comptes (admin, employee, client) |
| `menus` | Catalogue des menus avec allergènes, régimes, personnes_min |
| `horaires` | Horaires d'ouverture par jour |
| `commandes` | Commandes avec livraison km et remise |
| `commande_items` | Détail des articles par commandes |
| `avis` | Avis clients avec modération |

### Table orientée document

| Table | Description |
|-------|-------------|
| `nosql_documents` | Stockage JSON par collection (stats_menu_ stats_daily) |

Chaque document est une ligne avec `collection` (nom logique), `doc_id` (identifiant unique)
et `data` (colonne JSON native MySQL 5.7+).

---

## 5. Fonctionnalités

### Calcul de livraison kilométrique
- Géocodage via **Nominatim (OpenStreetMap)** - sans clé API
- Autocomplète avec debounce 500ms
- Distance calculé par **formule de Haversine** depuis Bordeaux centre
- Tarif : **5€ fixe + 0.54 €/km** affiché en temps réel dans le panier

### Remise fidélité 10%
- S'active si `nb_personnes >/= personnes_min +5`
- Pirx barré + badge `-10%` dans le panier
- Recalcul côté serveur à la validation

### Minimum de personnes
- Panier vérrouilé au `personnes_min` le plus élevé des menus sélectionnés
- Bouton `-` grisé avec message explicatif

### Suivi en temps réel
- Polling JSON 
- Barre de progression : En attente -> En préparation -> Prêt -> Livré

### Statistiques bi-base
- KPIs depuis les tables relationnelles MySQL
- Graphique d'évolution depuis la table `nosql_documents`
- Resynchronisation manuelle (Admin -> Statistique -> Resync)
- Synchronisation automatique après chaque nouvelle commande

---

## 6. Prérequis
- **xampp** 8.1+ - https://apachefriends.org
- Apage + MySQL inclus
- PHP 8.1 avec PDO_MySQL (actif par défaut dans xampp)

---

## 7. Installation locale (XAMPP)

### Etape 1 - Cloner le dépôt

Ourvrir un terminal et lancer :

```
git clone https://github.com/Kaara69/ViteEtGourmand.git
```

Copier le dossier cloné dans le répertoire htdocs de XAMPP

### Etape 2 - Installer  les dépendances

Dans le terminal, se placer dans le dossier du projet et lancer : 

```
cd C:\xampp\htdocs\ViteEtGourmand
composer install
```

Cela crée automatiquement le dossier 'vendor/' avec PHPMailer.

---

### 3. Créer la base de données

1. Lancer XAMPP et démarrer **Apache** et **MySQL**
2. Ouvrir [phpMyAdmin](http://localhost/phpmyadmin)
3. Cliquer sur **Nouvelle base de données**
4. Nommer la base `vite_gourmand` et valider
5. Cliquer sur **Importer**
6. Sélectionner le fichier `vite_gourmand.sql` à la racine du projet
7. Cliquer sur **Exécuter**

---

### 4. Configurer la connexion BDD

Ouvrir `config.php` à la racine et vérifier les paramètres :

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');  // ou 3306 selon config XAMPP
define('DB_NAME', 'vite_gourmand');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/ViteEtGourmand/');
```

---

### 5. Configurer les mails

Créer un fichier `.env` à la racine du projet (ce fichier n'est pzas sur GitHub) : 

MAIL_USER=identifiant_mailtrap
MAIL_PASS=mot_de_passe_mailtrap

Les identifiants sont disponibles sur [Mailtrap.io](https://mailtrap.io) -> Boîte à sable -> Intégrations -> PHPMailer

---

### 6. Lancer le site

Vérifier que **Apache** et **MySQL** sont bien démarrés dans le panneau XAMPP, puis ouvrir dans un navigateur : 

http://localhost/ViteEtGourmand/


## 8. Déploiement


<!-- installation composer phpmailer -->
<!-- config mail trap -->
