# 🌿 Au fil des saisons

> **Découvrez les produits de saison et cuisinez des recettes adaptées à votre région et à la météo.**

**Au fil des saisons** est une application web développée avec **Symfony 8** permettant de découvrir les produits alimentaires de saison en France, de créer et partager des recettes, tout en proposant des suggestions culinaires adaptées à la météo locale grâce à l'intégration de l'API **OpenWeather**.

L'objectif du projet est de promouvoir une alimentation locale, responsable et respectueuse des saisons.

![Symfony](https://img.shields.io/badge/Symfony-8-000000?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)
![Doctrine](https://img.shields.io/badge/Doctrine-ORM-red)
![Twig](https://img.shields.io/badge/Twig-Template-green?logo=twig)
![MySQL](https://img.shields.io/badge/MySQL-8-blue?logo=mysql)
![SQLite](https://img.shields.io/badge/SQLite-dev-003B57?logo=sqlite)
![License](https://img.shields.io/badge/license-MIT-green)

---

# 📖 Sommaire

- Aperçu
- Fonctionnalités
- Stack technique
- APIs utilisées
- Prérequis
- Installation
- Configuration
- Lancer le projet
- Structure du projet
- Modèle de données
- Identité visuelle
- Roadmap
- Auteur
- Licence

---

# 🖼️ Aperçu

<!-- Ajoute ici tes captures d'écran -->

```text
docs/screenshots/

home.png
produits.png
recettes.png
profil.png
admin.png
```

---

# ✨ Fonctionnalités

## 🍽️ Gestion des recettes

- Recherche de recettes par saison
- Recherche par produits de saison
- Création de recettes
- Modification
- Suppression
- Partage avec la communauté

## 🥕 Produits

- Fruits
- Légumes
- Viandes
- Poissons
- Recherche par mois
- Recherche par saison

## 👤 Utilisateurs

- Inscription
- Connexion
- Gestion du profil
- Avatar
- Favoris
- Notes et avis

## 🌤️ Météo

- Géolocalisation de l'utilisateur
- Affichage de la météo locale
- Température
- Humidité
- Vent
- Conditions météorologiques
- Suggestions de recettes adaptées à la météo

## 🛠️ Administration

- Gestion des utilisateurs
- Gestion des produits
- Gestion des recettes
- Gestion des saisons
- Validation des recettes
- Modération des avis

## 📱 Interface

- Responsive Design
- Mobile First

---

# 🛠️ Stack technique

| Domaine | Technologie |
|----------|-------------|
| Backend | Symfony 8 |
| Langage | PHP 8.2 |
| Templating | Twig |
| Base de données | SQLite (développement) / MySQL (production) |
| ORM | Doctrine |
| Sécurité | Symfony Security |
| Frontend | HTML5 / CSS3 |
| API météo | OpenWeather API |

---

# 🌤️ API utilisée

## ☀️ OpenWeather API

Les coordonnées récupérées grâce à la recherche de localité via l'API OpenWeather afin d'obtenir la météo locale.

Les informations récupérées sont :

- Température actuelle
- Température ressentie
- Conditions météorologiques
- Icône météo

Ces informations permettent de proposer des recettes adaptées à la météo ainsi qu'à la saison.

---

# ✅ Prérequis

- PHP 8.2 ou supérieur
- Composer
- Symfony CLI (recommandé)
- Extensions PHP :
  - ctype
  - iconv
  - pdo_sqlite (développement)
  - pdo_mysql (production)

---

# 🚀 Installation

```bash
# Cloner le dépôt
git clone https://github.com/<ton-utilisateur>/au-fil-des-saisons.git

cd au-fil-des-saisons

# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env .env.local
```

---

# ⚙️ Configuration

Dans `.env.local` :

```env
APP_ENV=dev

APP_SECRET=change_moi

DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

OPENWEATHER_API_KEY=VotreCleAPI
```

Production :

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/au_fil_des_saisons?serverVersion=8.0"
```

---

# ▶️ Lancer le projet

```bash
php bin/console doctrine:database:create

php bin/console doctrine:migrations:migrate

php bin/console doctrine:fixtures:load

symfony server:start
```

ou

```bash
php -S 127.0.0.1:8000 -t public/
```

L'application est ensuite accessible sur :

```
http://127.0.0.1:8000
```

---

# 📁 Structure du projet

```text
au-fil-des-saisons/
├── assets/
├── config/
├── docs/
├── migrations/
├── public/
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   ├── Security/
│   └── Service/
├── templates/
├── tests/
└── var/
```

---

# 🗂️ Modèle de données

Le modèle de données repose sur plusieurs entités principales :

- Utilisateur
- Recette
- Produit
- Saison
- Mois
- Avis
- Favoris

Relations principales :

- Utilisateur → Recettes
- Utilisateur → Favoris
- Utilisateur → Avis
- Recette ↔ Produit (N:N)
- Produit ↔ Saison (N:N)
- Recette ↔ Saison (N:N)

Les diagrammes UML, MCD et MPD sont disponibles ci-dessous.

<img width="1037" height="1242" alt="ER _ au fil des saisons drawio(1)" src="https://github.com/user-attachments/assets/c2e3eb4f-d4ce-4e56-ad07-32d68b349dc5" />


<img width="6331" height="2207" alt="Copy of User Recipe Ecosystem-2026-07-03-092200" src="https://github.com/user-attachments/assets/b9a47f24-98de-4a93-ae60-a58250ec5429" />





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

# 🎨 Identité visuelle

**Palette**

🌿 Vert forêt : `#2F4B33`

🍃 Vert mousse : `#3C5C40`

🌾 Ambre : `#E8A33D`

🤍 Crème : `#FBF6EC`

**Typographie**

- Titres : Cormorant Garamond
- Texte : Poppins

**Style**

- Mobile First
- Boutons arrondis
- Interface naturelle inspirée des saisons

---

# 🚀 Roadmap

## Fonctionnalités réalisées

- ✅ Authentification
- ✅ Gestion des profils
- ✅ CRUD Produits
- ✅ CRUD Recettes
- ✅ Gestion des saisons
- ✅ Favoris
- ✅ Avis

## Évolutions prévues

- 🔄 Intégration complète de l'API OpenWeather
- 🔄 Suggestions intelligentes selon la météo
- 🔄 Carte interactive des régions françaises
- 🔄 Tableau de saisonnalité par région

---

# 👩‍💻 Auteur

**Bérangère Brunat**

Projet réalisé dans le cadre de la formation **Développeur Web et Web Mobile**.

🎓 Soutenance prévue : **7 septembre 2026**

---

# 📄 Licence

Ce projet est distribué sous licence **MIT**.

Voir le fichier **LICENSE** pour plus d'informations.

