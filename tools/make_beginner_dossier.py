#!/usr/bin/env python3
"""Crée une version pédagogique du dossier, destinée à un grand débutant."""

from pathlib import Path
from tempfile import TemporaryDirectory
from zipfile import ZIP_DEFLATED, ZipFile
import xml.etree.ElementTree as ET

from enrich_dossier_project import NS, W, make_paragraph, page_break, paragraph_text, qn, replace_paragraph


SOURCE = Path("/home/user/Au_fil_des_saisons/dossier_projet_au_fil_des_saisons_corrige.docx")
DESTINATION = Path("/home/user/Au_fil_des_saisons/dossier_projet_au_fil_des_saisons_version_debutant.docx")


def style_of(paragraph: ET.Element) -> str:
    style = paragraph.find("./w:pPr/w:pStyle", NS)
    return style.get(qn("val"), "") if style is not None else ""


def text_paragraph(text: str) -> ET.Element:
    return make_paragraph(text)


def label_paragraph(text: str) -> ET.Element:
    paragraph = make_paragraph(text)
    run = paragraph.find("w:r", NS)
    run_properties = ET.SubElement(run, qn("rPr"))
    ET.SubElement(run_properties, qn("b"))
    shading = paragraph.find("w:pPr", NS)
    shd = ET.SubElement(shading, qn("shd"))
    shd.set(qn("fill"), "EAF4EA")
    return paragraph


def diagram(lines: str) -> list[ET.Element]:
    return [make_paragraph(line, code=True) for line in lines.strip("\n").splitlines()]


def insert_after(body: ET.Element, target: ET.Element, elements: list[ET.Element]) -> None:
    index = list(body).index(target) + 1
    for element in elements:
        body.insert(index, element)
        index += 1


def find_heading(body: ET.Element, title: str) -> ET.Element:
    for paragraph in body.findall("./w:p", NS):
        if paragraph_text(paragraph) == title:
            return paragraph
    raise RuntimeError(f"Titre introuvable : {title}")


def add_after_heading(body: ET.Element, heading: str, content: list[ET.Element]) -> None:
    insert_after(body, find_heading(body, heading), content)


def main() -> None:
    with TemporaryDirectory() as temp_directory:
        temp = Path(temp_directory)
        with ZipFile(SOURCE) as archive:
            archive.extractall(temp)

        document_path = temp / "word" / "document.xml"
        tree = ET.parse(document_path)
        root = tree.getroot()
        body = root.find("w:body", NS)
        if body is None:
            raise RuntimeError("Corps du document introuvable")

        # La couverture annonce clairement l'objectif pédagogique de cette version.
        for paragraph in body.findall("./w:p", NS):
            text = paragraph_text(paragraph)
            if text == "Dossier de projet professionnel":
                replace_paragraph(paragraph, "Dossier de projet professionnel — version pédagogique")
            elif text == "Version 1.1 — version corrigée et enrichie":
                replace_paragraph(paragraph, "Version 2.0 — reformulée pour un lecteur débutant")

        add_after_heading(body, "Introduction", [
            label_paragraph("Comment lire ce dossier ?"),
            text_paragraph("Ce dossier a été écrit pour pouvoir être compris sans connaissances préalables en développement web. Chaque notion technique est d'abord expliquée avec des mots simples, puis reliée à un élément concret de l'application. Les schémas se lisent de haut en bas ou de gauche à droite en suivant les flèches."),
            text_paragraph("Lorsqu'un extrait de code est présenté, il ne faut pas chercher à mémoriser chaque symbole. Les commentaires qui commencent par // expliquent l'intention de la ligne. L'objectif est de comprendre le trajet d'une information : ce que saisit l'utilisateur, ce que fait Symfony, puis ce qui est enregistré dans la base de données."),
            label_paragraph("Petit vocabulaire indispensable"),
            text_paragraph("Application web : programme utilisé dans un navigateur. Front-end : partie visible par l'utilisateur. Back-end : partie qui reçoit les demandes, applique les règles et dialogue avec la base de données. Route : adresse associée à une action. Entité : objet PHP qui représente une donnée enregistrée. API : service extérieur qu'une application peut interroger. Fixture : jeu de fausses données cohérentes, utilisé pour préparer une démonstration ou des tests."),
        ])

        add_after_heading(body, "Chapitre 6 — Conception de la base de données", [
            label_paragraph("Idée simple du chapitre"),
            text_paragraph("La base de données est comparable à un ensemble de tableaux reliés entre eux. Un tableau contient les utilisateurs, un autre les produits et un autre les recettes. Chaque ligne possède un identifiant unique. Les relations évitent de recopier plusieurs fois la même information."),
            *diagram("""
[UTILISATEUR] 1 ───── crée ───── 0..N [RECETTE]
                                      │
                                      │ utilise
                                      ▼
                                 1..N [PRODUIT]
                                      │
                                      │ appartient à
                                      ▼
                                 1..N [SAISON]

Lecture : un utilisateur peut créer aucune, une ou plusieurs recettes.
Une recette utilise un ou plusieurs produits.
Un produit peut être associé à plusieurs saisons.
"""),
        ])

        add_after_heading(body, "6.3 Le MCD", [
            label_paragraph("MCD en mots simples"),
            text_paragraph("Le Modèle Conceptuel de Données est un brouillon métier. Il montre les informations nécessaires et leurs liens, sans parler encore de PHP, de Symfony ou de MySQL. Il répond par exemple à la question : « une recette peut-elle contenir plusieurs produits ? »"),
        ])
        add_after_heading(body, "6.4 Le MLD", [
            label_paragraph("MLD en mots simples"),
            text_paragraph("Le Modèle Logique de Données transforme le brouillon en futures tables. Une relation plusieurs-à-plusieurs, comme entre RECETTE et PRODUIT, devient une table intermédiaire. Cette table contient principalement les identifiants de la recette et du produit."),
            *diagram("""
[RECETTE]              [RECETTE_PRODUIT]              [PRODUIT]
id  1 ───────────────► recette_id                     id  8
titre                  produit_id ◄────────────────── nom

Exemple : la ligne (recette_id = 1, produit_id = 8)
signifie que le produit 8 est utilisé dans la recette 1.
"""),
        ])
        add_after_heading(body, "6.5 Le MPD", [
            label_paragraph("MPD en mots simples"),
            text_paragraph("Le Modèle Physique de Données est la version réellement destinée à MySQL. Il précise les noms des tables, les colonnes, les types de données et les clés étrangères. Une clé étrangère est un identifiant qui pointe vers une ligne d'une autre table."),
        ])

        add_after_heading(body, "Chapitre 7 — Architecture de l'application", [
            label_paragraph("Pourquoi séparer l'application ?"),
            text_paragraph("L'application est divisée en petites responsabilités. Cette organisation évite de placer tout le code au même endroit. Elle facilite la lecture, les corrections et les évolutions."),
            *diagram("""
1. L'utilisateur clique ou valide un formulaire
                         │
                         ▼
2. ROUTE ───── trouve le bon CONTRÔLEUR
                         │
                         ▼
3. CONTRÔLEUR ─ applique les règles de l'action
       │                 │
       ▼                 ▼
   DOCTRINE          SERVICE MÉTÉO
       │
       ▼
4. BASE DE DONNÉES
       │
       ▼
5. TWIG fabrique la page HTML renvoyée au navigateur
"""),
        ])
        add_after_heading(body, "MVC", [
            label_paragraph("MVC sans jargon"),
            text_paragraph("MVC signifie Modèle, Vue, Contrôleur. Le Modèle représente les données et leurs règles. La Vue est la page affichée, construite avec Twig. Le Contrôleur reçoit la demande de l'utilisateur et coordonne le travail. On peut comparer le contrôleur à un chef d'orchestre : il ne fait pas tout lui-même, mais appelle les bons composants."),
        ])
        add_after_heading(body, "Les Repositories", [
            text_paragraph("Un Repository regroupe les recherches dans la base de données. Par exemple, ProductRepository peut demander « donne-moi les produits correspondant à cette saison ». Cela évite d'écrire les requêtes directement dans les pages."),
        ])
        add_after_heading(body, "Les Services", [
            text_paragraph("Un Service contient une tâche réutilisable qui ne dépend pas directement d'une page. WeatherService, par exemple, sait interroger OpenWeatherMap et transformer sa réponse en données simples pour le reste de l'application."),
        ])

        add_after_heading(body, "Chapitre 8 — Docker", [
            label_paragraph("Docker avec une comparaison simple"),
            text_paragraph("Docker prépare des boîtes isolées appelées conteneurs. Chaque boîte possède un rôle et les outils nécessaires à ce rôle. Ainsi, l'application fonctionne dans un environnement identique sur les différentes machines, sans installer manuellement chaque composant."),
            *diagram("""
┌──────────────────── DOCKER ────────────────────┐
│                                                │
│  [Conteneur PHP] ───────► [Conteneur MySQL]    │
│   exécute Symfony          conserve les données │
│                                                │
│  [Conteneur Adminer] ───► permet de consulter  │
│                           la base dans le web   │
└────────────────────────────────────────────────┘
"""),
        ])

        add_after_heading(body, "Chapitre 9 — Développement Symfony", [
            label_paragraph("But de ce chapitre"),
            text_paragraph("Symfony fournit une structure et des outils déjà éprouvés : routes, formulaires, sécurité, validation et accès aux données. Le projet assemble ces outils pour répondre aux besoins d'Au fil des saisons."),
        ])
        add_after_heading(body, "9.3 Pourquoi Doctrine ?", [
            text_paragraph("Doctrine sert de traducteur entre les objets PHP et les tables MySQL. Le code manipule un objet Recette ; Doctrine transforme ensuite cette opération en requête SQL. Cela rend le code plus lisible et limite les requêtes écrites à la main."),
            *diagram("""
Objet PHP Recette
      │  persist() puis flush()
      ▼
Doctrine ORM
      │  génère une requête SQL préparée
      ▼
Table RECETTE dans MySQL
"""),
        ])
        add_after_heading(body, "9.9 Installation et Composer", [
            text_paragraph("Composer est le gestionnaire de dépendances de PHP. Une dépendance est une bibliothèque créée par d'autres développeurs et utilisée par le projet. composer.json décrit les besoins ; composer.lock conserve les versions exactes afin que toute l'équipe installe le même ensemble."),
        ])
        add_after_heading(body, "9.10 Routing", [
            text_paragraph("Une route associe une adresse et une méthode HTTP à une fonction du contrôleur. GET sert généralement à lire une page. POST sert à envoyer ou modifier des données."),
            *diagram("""
GET /recettes/nouvelle  ─► affiche le formulaire
POST /recettes/nouvelle ─► vérifie puis enregistre le formulaire
GET /recettes/15        ─► affiche la recette numéro 15
"""),
        ])
        add_after_heading(body, "9.12 Migrations et fixtures", [
            label_paragraph("Ne pas confondre migration et fixture"),
            text_paragraph("Une migration modifie la structure : elle crée par exemple une table ou une colonne. Une fixture ajoute du contenu de démonstration : mois, saisons, produits, recettes et utilisateur fictif. La migration construit les étagères ; la fixture les remplit."),
            *diagram("""
MIGRATIONS                      FIXTURES
structure                       contenu de démonstration
    │                                      │
    ▼                                      ▼
tables et colonnes             12 mois + 4 saisons
relations et contraintes       100 produits + recettes
                               1 utilisateur fictif

Ordre de chargement :
MonthFixtures ─┐
               ├─► ProductFixtures ─┐
SeasonFixtures ┘                     ├─► RecipeFixtures
UserFixtures ────────────────────────┘
"""),
        ])
        add_after_heading(body, "9.14 CRUD Produits, Recettes et Utilisateurs", [
            text_paragraph("CRUD résume quatre actions courantes : Create (créer), Read (consulter), Update (modifier) et Delete (supprimer). Pour une recette, cela correspond à ajouter une recette, afficher sa fiche, la corriger puis éventuellement la supprimer."),
        ])
        add_after_heading(body, "9.16 Exemple détaillé : la création d'une recette", [
            label_paragraph("Le parcours complet en une vue"),
            *diagram("""
Utilisateur
   │ remplit puis valide
   ▼
RecetteType (formulaire)
   │ transforme et contrôle les données
   ▼
RecetteController::processForm()
   │ vérifie isSubmitted() et isValid()
   │ attribue le statut et calcule les saisons
   ▼
EntityManager / Doctrine
   │ persist() prépare, flush() enregistre
   ▼
MySQL
   │
   ▼
Redirection vers la liste des recettes
"""),
        ])

        add_after_heading(body, "Chapitre 10 — API météo", [
            label_paragraph("Qu'est-ce qu'une API ?"),
            text_paragraph("Une API est une porte d'entrée prévue pour permettre à deux logiciels de communiquer. L'application envoie le nom d'une ville à OpenWeatherMap. Le service répond avec du JSON, un format texte structuré. WeatherService garde uniquement les données utiles et prépare un conseil culinaire."),
            *diagram("""
Utilisateur saisit « Toulouse »
              │
              ▼
HomeController appelle WeatherService
              │ requête HTTPS + clé secrète
              ▼
API OpenWeatherMap
              │ réponse JSON
              ▼
WeatherService vérifie et simplifie les données
              │
              ▼
Twig affiche température, vent, humidité et conseil

En cas d'erreur : ville inconnue, clé invalide ou service indisponible
→ un message clair est affiché au lieu d'une erreur technique brute.
"""),
        ])

        add_after_heading(body, "Chapitre 11 — Sécurité", [
            label_paragraph("Principe général"),
            text_paragraph("Le navigateur ne doit jamais être considéré comme une source fiable. Une personne peut modifier une adresse, un formulaire ou une requête. L'application vérifie donc l'identité, le rôle, le jeton CSRF et la validité des données côté serveur avant toute modification."),
            *diagram("""
Requête reçue
    │
    ├─ L'utilisateur est-il connecté ?
    ├─ Possède-t-il le rôle nécessaire ?
    ├─ Le jeton CSRF est-il valide ?
    ├─ Les données respectent-elles les contraintes ?
    │
    └─ OUI à tous les contrôles ─► action autorisée
"""),
        ])
        add_after_heading(body, "Protection CSRF", [
            text_paragraph("Une attaque CSRF essaie de faire valider une action à l'utilisateur sans son intention. Symfony ajoute au formulaire un jeton secret lié à la session. Le serveur refuse l'action si le jeton reçu ne correspond pas à celui attendu."),
        ])
        add_after_heading(body, "Injection SQL", [
            text_paragraph("Une injection SQL cherche à transformer une saisie en commande pour la base. Doctrine sépare la structure de la requête et les valeurs fournies par l'utilisateur grâce aux requêtes préparées. Une saisie reste donc une valeur et n'est pas exécutée comme une instruction SQL."),
        ])
        add_after_heading(body, "Faille XSS (Cross-Site Scripting)", [
            text_paragraph("Une attaque XSS tente d'enregistrer du code JavaScript dans un champ, par exemple un commentaire. Twig échappe les caractères spéciaux par défaut : le texte est affiché comme du texte au lieu d'être exécuté comme un programme."),
        ])

        add_after_heading(body, "Chapitre 12 — Tests", [
            label_paragraph("Pourquoi tester ?"),
            text_paragraph("Un test automatisé exécute une petite partie du programme avec des données connues, puis compare le résultat obtenu au résultat attendu. Si une future modification casse le comportement, le test échoue immédiatement."),
            *diagram("""
Donnée préparée : 27,4 °C et vent à 3,5 m/s
                         │
                         ▼
             WeatherService est exécuté
                         │
                         ▼
Résultats attendus : 27 °C, 12,6 km/h, conseil estival
                         │
              résultat identique ?
                  OUI ─► test réussi
                  NON ─► régression à corriger
"""),
            text_paragraph("État réel du projet : deux tests unitaires et cinq assertions vérifient actuellement WeatherService. Les parcours complets d'inscription, de connexion et de création de recette devront encore être couverts par des tests fonctionnels."),
        ])

        add_after_heading(body, "Annexe I — Fixtures et extraits de code commentés", [
            label_paragraph("Méthode de lecture des extraits"),
            text_paragraph("Lire d'abord le texte placé avant l'extrait, puis suivre les commentaires // dans le code. Les accolades { } délimitent un bloc d'instructions. Le symbole -> signifie qu'une méthode est appelée sur un objet. Le symbole :: permet d'accéder à une classe, une constante ou une valeur d'énumération."),
        ])

        tree.write(document_path, encoding="UTF-8", xml_declaration=True)

        settings_path = temp / "word" / "settings.xml"
        settings_tree = ET.parse(settings_path)
        settings_root = settings_tree.getroot()
        update_fields = settings_root.find("w:updateFields", NS)
        if update_fields is None:
            update_fields = ET.SubElement(settings_root, qn("updateFields"))
        update_fields.set(qn("val"), "true")
        settings_tree.write(settings_path, encoding="UTF-8", xml_declaration=True)

        with ZipFile(DESTINATION, "w", ZIP_DEFLATED) as archive:
            for path in temp.rglob("*"):
                if path.is_file():
                    archive.write(path, path.relative_to(temp).as_posix())

    print(f"Document créé : {DESTINATION}")


if __name__ == "__main__":
    main()
