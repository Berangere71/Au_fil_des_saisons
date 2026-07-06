# 🌿 Au fil des saisons

Application web de découverte et de gestion de recettes de saison, permettant aux utilisateurs de trouver des idées de plats selon les produits (fruits, légumes, viandes, poissons) disponibles.

![Symfony](https://img.shields.io/badge/Symfony-8-000000?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)
![License](https://img.shields.io/badge/license-MIT-green)

---

## 📖 Sommaire

- [Aperçu](#-aperçu)
- [Fonctionnalités](#-fonctionnalités)
- [Stack technique](#-stack-technique)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Lancer le projet](#-lancer-le-projet)
- [Structure du projet](#-structure-du-projet)
- [Modèle de données](#-modèle-de-données)
- [Identité visuelle](#-identité-visuelle)
- [Auteur](#-auteur)
- [Licence](#-licence)

---

## 🖼️ Aperçu

<!-- Ajoute ici tes captures d'écran -->
<!-- ![Accueil](docs/screenshots/home.png) -->
<!-- ![Espace admin](docs/screenshots/admin.png) -->

## ✨ Fonctionnalités

- 🔎 Recherche et découverte de recettes selon la saison en cours
- 🍅 Association Recette ↔ Produit ↔ Saison (relations many-to-many)
- 👤 Authentification et gestion de profil utilisateur (Symfony Security)
- 🛠️ Espace d'administration pour gérer recettes, produits et saisons
- 📱 Interface responsive, pensée mobile-first
- 📅 Affichage des recettes organisé par accordéons saisonniers (printemps → été → automne → hiver)

## 🛠️ Stack technique

| Domaine        | Techno                              |
|----------------|--------------------------------------|
| Backend        | Symfony 8                            |
| Templating     | Twig                                 |
| Base de données| SQLite (dev) / MySQL (prod)          |
| Sécurité       | Symfony Security                     |
| Frontend       | CSS                                  |
| ORM            | Doctrine                             |

## ✅ Prérequis

- PHP 8.2 ou supérieur
- Composer
- Symfony CLI (recommandé)
- Extensions PHP : `ctype`, `iconv`, `pdo_sqlite` (dev) ou `pdo_mysql` (prod)

## 🚀 Installation

```bash
# Cloner le repo
git clone https://github.com/<ton-utilisateur>/au-fil-des-saisons.git
cd au-fil-des-saisons

# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env .env.local
```

## ⚙️ Configuration

Dans `.env.local`, adapte les variables selon ton environnement :

```env
# Développement (SQLite)
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# Production (MySQL)
# DATABASE_URL="mysql://user:password@127.0.0.1:3306/au_fil_des_saisons?serverVersion=8.0"

APP_ENV=dev
APP_SECRET=change_moi
```

## ▶️ Lancer le projet

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Charger les données de démo (optionnel)
php bin/console doctrine:fixtures:load

# Lancer le serveur
symfony server:start
# ou
php -S 127.0.0.1:8000 -t public/
```

L'application est ensuite accessible sur `http://127.0.0.1:8000`.

## 📁 Structure du projet

```
au-fil-des-saisons/
├── config/           # Configuration Symfony
├── migrations/        # Migrations Doctrine
├── public/            # Point d'entrée web
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   └── Security/
├── templates/          # Vues Twig
├── assets/             # CSS
└── tests/
```

## 🗂️ Modèle de données

Le modèle relationnel repose sur trois entités centrales — **Recette**, **Produit** et **Saison** — reliées par des relations many-to-many, permettant à une recette d'appartenir à plusieurs produits et saisons.

L'UML :
<img width="1037" height="1242" alt="ER _ au fil des saisons drawio(1)" src="https://github.com/user-attachments/assets/5e037759-a1ff-42b8-9f81-42ddd13f3835" />


Le MCD :
<img width="8192" height="5568" alt="User Recipe Ecosystem-2026-07-03-092009" src="https://github.com/user-attachments/assets/96de1d50-1335-457c-a6c6-3631dea8041e" />

Le MPD : title: Au fil des saisons — MPD (Modèle Physique de Données)
```mermaid

erDiagram
 
USER {
INT id PK "AUTO_INCREMENT"
VARCHAR(100) prenom "NOT NULL"
VARCHAR(100) nom "NOT NULL"
VARCHAR(180) email "NOT NULL UNIQUE"
VARCHAR(255) password "NOT NULL"
VARCHAR(255) photo "NULL"
ENUM role "visiteur,utilisateur,administrateur DEFAULT visiteur"
BOOLEAN is_blocked "NOT NULL DEFAULT 0"
DATETIME created_at "NOT NULL DEFAULT CURRENT_TIMESTAMP"
}
 
RECETTE {
INT id PK "AUTO_INCREMENT"
VARCHAR(150) titre "NOT NULL"
VARCHAR(255) photo "NULL"
ENUM type_plat "entree,plat,dessert NOT NULL"
INT user_id FK "NOT NULL"
FLOAT nbr_person "NOT NULL"
INT time_prepa "NULL (en minutes)"
TEXT ingredient "NOT NULL"
TEXT preparation "NOT NULL"
BOOLEAN is_oven "NOT NULL DEFAULT 0"
FLOAT temp_oven "NULL"
FLOAT time_oven "NULL"
BOOLEAN is_public "NOT NULL DEFAULT 0"
ENUM statut "attente,publiee,rejetee,signalee DEFAULT attente"
DATETIME created_at "NOT NULL DEFAULT CURRENT_TIMESTAMP"
}
 
PRODUCT {
INT id PK "AUTO_INCREMENT"
VARCHAR(100) nom "NOT NULL"
ENUM category "legume,fruit,viande,poisson NOT NULL"
VARCHAR(255) photo "NULL"
INT debut_recolte_mois_id FK "NOT NULL"
INT fin_recolte_mois_id FK "NOT NULL"
TEXT conservation "NOT NULL"
DATETIME created_at "NOT NULL DEFAULT CURRENT_TIMESTAMP"
}
 
SEASON {
INT id PK "AUTO_INCREMENT"
ENUM name_season "printemps,ete,automne,hiver NOT NULL UNIQUE"
DATETIME created_at "NOT NULL DEFAULT CURRENT_TIMESTAMP"
}
 
MONTH {
INT id PK "AUTO_INCREMENT"
ENUM name_month "janvier,fevrier,mars,avril,mai,juin,
juillet,aout,septembre,octobre,novembre,decembre NOT NULL UNIQUE"
INT month_order "NOT NULL UNIQUE"
}
 
FAVORIS {
INT user_id FK "NOT NULL PK"
INT recette_id FK "NOT NULL PK"
DATETIME date_ajout "NOT NULL DEFAULT CURRENT_TIMESTAMP"
}
 
RECETTE_PRODUCT {
INT recette_id FK "NOT NULL PK"
INT product_id FK "NOT NULL PK"
}
 
RECETTE_SEASON {
INT recette_id FK "NOT NULL PK"
INT season_id FK "NOT NULL PK"
}
 
PRODUCT_SEASON {
INT product_id FK "NOT NULL PK"
INT season_id FK "NOT NULL PK"
}
 
AVIS {
INT id PK "AUTO_INCREMENT"
INT user_id FK "NOT NULL"
INT recette_id FK "NOT NULL"
INT note "NOT NULL CHECK 1-5"
BOOLEAN signale "NOT NULL DEFAULT 0"
TEXT motif_signalement "NULL"
DATETIME created_at "NOT NULL DEFAULT CURRENT_TIMESTAMP"
}
 
%% -----------------------------------------------
%% RELATIONS avec cardinalités physiques exactes
%% -----------------------------------------------
 
%% USER -> RECETTE : un utilisateur crée 0 à N recettes
USER ||--o{ RECETTE : "user_id"
 
%% USER -> FAVORIS : un utilisateur a 0 à N favoris
USER ||--o{ FAVORIS : "user_id"
 
%% USER -> AVIS : un utilisateur rédige 0 à N avis
USER ||--o{ AVIS : "user_id"
 
%% RECETTE -> FAVORIS : une recette est dans 0 à N favoris
RECETTE ||--o{ FAVORIS : "recette_id"
 
%% RECETTE -> AVIS : une recette reçoit 0 à N avis
RECETTE ||--o{ AVIS : "recette_id"
 
%% RECETTE <-> PRODUCT via table de jonction (N-N)
RECETTE ||--o{ RECETTE_PRODUCT : "recette_id"
PRODUCT ||--o{ RECETTE_PRODUCT : "product_id"
 
%% RECETTE <-> SEASON via table de jonction (N-N)
RECETTE ||--o{ RECETTE_SEASON : "recette_id"
SEASON ||--o{ RECETTE_SEASON : "season_id"
 
%% PRODUCT <-> SEASON via table de jonction (N-N)
PRODUCT ||--o{ PRODUCT_SEASON : "product_id"
SEASON ||--o{ PRODUCT_SEASON : "season_id"
 
%% PRODUCT -> MONTH : période de récolte (début et fin)
MONTH ||--o{ PRODUCT : "debut_recolte_mois_id"
MONTH ||--o{ PRODUCT : "fin_recolte_mois_id"
```

## 🎨 Identité visuelle

- **Couleurs** : vert forêt (`#2F4B33` / `#3C5C40`), ambre (`#E8A33D`), crème (`#FBF6EC`)
- **Typographie** : Cormorant Garamond (italique pour les titres)
- **Style** : boutons pilule arrondis, esthétique naturelle et organique

## 👩‍💻 Auteur

**Bérangère Brunat**
Projet réalisé dans le cadre d'une formation développeur web et web mobile.
Soutenance prévue le **7 septembre 2026**.

## 📄 Licence

Ce projet est distribué sous licence MIT — voir le fichier [LICENSE](LICENSE) pour plus de détails.
