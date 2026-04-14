# Vite & Gourmand — Documentation technique

> Projet ECF — Application web de commande en ligne pour un traiteur bordelais.

---

## Sommaire

1. [Présentation](#1-présentation)
2. [Structure des fichiers](#2-structure-des-fichiers)



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
├── .htaccess                   ← Sécurité (protection dossiers)
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