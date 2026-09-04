# Rapport d’audit d’accessibilité — RGAA 4.1.2

## 1. Informations générales

| Élément | Valeur |
|---|---|
| Projet | Au fil des saisons |
| Référentiel | RGAA 4.1.2 — 106 critères, 13 thématiques |
| Date | 3 septembre 2026 |
| Type d’audit | Audit technique initial du code et d’un échantillon de pages rendues |
| Technologies | HTML5, CSS, JavaScript, Twig, Symfony 7.4 |
| Statut retenu | **Non conforme en l’absence d’un audit manuel exhaustif** |

Ce document n’est pas encore une déclaration légale de conformité. Il présente les contrôles effectués, les corrections intégrées et les vérifications restant à réaliser.

## 2. Périmètre

### Pages publiques contrôlées

- accueil : `/` ;
- connexion : `/login` ;
- création de compte : `/register` ;
- catalogue des produits : `/products` ;
- recherche ;
- détail d’un produit ;
- mentions légales et pages liées à la protection des données.

### Pages authentifiées examinées dans le code

- profil et modification du profil ;
- création, modification, consultation et liste des recettes ;
- commentaires, réponses, notation et signalements ;
- administration des utilisateurs, produits, recettes et signalements.

### Inventaire technique

- 32 gabarits Twig ;
- 17 gabarits contenant un formulaire ;
- 3 tableaux de données ;
- 16 fichiers JavaScript ou assets applicatifs examinés ;
- versions mobile et ordinateur des styles communs.

L’échantillon devra être complété avec des comptes utilisateur et administrateur disposant de données réalistes avant l’audit final.

## 3. Méthode

Les contrôles ont été réalisés par :

1. examen du HTML généré par Symfony ;
2. examen statique des gabarits Twig, CSS et JavaScript ;
3. détection des images sans alternative, champs sans nom accessible, boutons ou liens sans intitulé et identifiants dupliqués ;
4. calcul des principaux contrastes de la charte ;
5. vérification des composants au clavier à partir de leur implémentation ;
6. validation syntaxique Twig/YAML et exécution des tests automatisés.

Les tests automatiques ne permettent pas, à eux seuls, de contrôler les 106 critères RGAA. Les résultats ci-dessous distinguent donc les points contrôlés des tests manuels à compléter.

## 4. Synthèse par thématique RGAA

| Nº | Thématique | Résultat de l’audit initial | Observations |
|---:|---|---|---|
| 1 | Images | Partiellement conforme | Les images rendues possèdent un attribut `alt`. La pertinence des alternatives des images ajoutées par les utilisateurs reste à contrôler manuellement. |
| 2 | Cadres | Non applicable dans l’échantillon | Aucun `iframe` détecté. À réévaluer si un contenu externe est ajouté. |
| 3 | Couleurs | Partiellement conforme | Les contrastes principaux ont été renforcés. Un contrôle exhaustif de tous les états et contenus dynamiques reste nécessaire. |
| 4 | Multimédia | Non applicable dans l’échantillon | Aucun contenu audio ou vidéo détecté. |
| 5 | Tableaux | Conforme sur les tableaux examinés | Les trois tableaux possèdent une légende et leurs en-têtes utilisent `scope="col"`. |
| 6 | Liens | Partiellement conforme | Les liens composés uniquement d’une icône ont reçu un nom accessible. La pertinence de tous les intitulés doit être confirmée en contexte. |
| 7 | Scripts | Partiellement conforme | Le menu gère ouverture, fermeture, Échap, boucle de focus et retour du focus. Les carrousels et champs dynamiques nécessitent un test manuel avec lecteur d’écran. |
| 8 | Éléments obligatoires | Conforme sur l’échantillon public | HTML5, `lang="fr"`, encodage UTF-8, titres de pages et structure générale présents. Vérifier l’unicité et la pertinence de chaque titre sur toutes les routes. |
| 9 | Structuration de l’information | Partiellement conforme | Régions, titres, listes, sections et définitions sont utilisés. La hiérarchie complète des titres reste à parcourir page par page. |
| 10 | Présentation de l’information | Partiellement conforme | Focus visible, réagencement responsive et réduction des animations ajoutés. Tests requis à 200 %, à 400 % et sur une largeur CSS de 320 px. |
| 11 | Formulaires | Partiellement conforme | Labels, légendes, messages en français, erreurs serveur et noms accessibles ont été améliorés. Les annonces d’erreur avec NVDA/VoiceOver restent à tester. |
| 12 | Navigation | Partiellement conforme | Lien d’évitement, zones de navigation, page courante et menu clavier présents. L’ordre de tabulation doit être validé sur tous les parcours. |
| 13 | Consultation | Partiellement conforme | `prefers-reduced-motion` est pris en compte. Le comportement sans CSS, sans JavaScript et avec personnalisation des couleurs reste à tester. |

## 5. Corrections déjà intégrées

### Navigation et clavier

- ajout du lien « Aller au contenu principal » ;
- ajout de l’identifiant `main-content` sur la zone principale ;
- focus clavier fortement visible sur liens, boutons, champs, listes et éléments interactifs ;
- retrait de l’`autofocus` sur la connexion et la recherche ;
- ouverture et fermeture du menu au clavier, touche Échap, confinement et restitution du focus ;
- indication de la page courante avec `aria-current` ;
- réduction des animations lorsque l’utilisateur l’a demandée.

### Images, icônes et composants

- alternatives textuelles présentes sur les images informatives examinées ;
- icônes décoratives masquées avec `aria-hidden="true"` ;
- noms accessibles ajoutés aux boutons et liens uniquement visuels ;
- boutons de carrousel nommés « Produits précédents » et « Produits suivants ».

### Formulaires

- étiquettes explicites pour les commentaires, réponses et motifs de signalement ;
- regroupement de la notation dans un `fieldset` avec une `legend` ;
- messages d’erreur et de confirmation en français ;
- messages flash exposés comme `alert` ou `status` ;
- indication des champs invalides et validation côté serveur.

### Tableaux

- légende accessible ajoutée aux tableaux de produits, utilisateurs et notes ;
- portée des cellules d’en-tête précisée avec `scope="col"` ;
- conteneurs permettant le défilement horizontal sur petit écran.

### Contrastes

- ambre de texte `#8A5200` : contraste de **5,76:1** sur `#F7F3E9` et **6,39:1** sur blanc ;
- icônes claires `#F2A93B` sur vert `#2F5E2E` : contraste de **3,81:1** ;
- boutons ambre foncé avec texte blanc : contraste de **6,39:1**.

## 6. Écarts et contrôles restant à traiter

### Priorité haute

1. Tester tous les parcours au clavier : inscription, connexion, création de recette, notation, signalement et administration.
2. Tester ces parcours avec NVDA + Firefox, puis VoiceOver + Safari.
3. Vérifier l’annonce et le rattachement de chaque erreur au champ concerné après soumission.
4. Mesurer tous les couples texte/fond et tous les états survol, focus, actif, désactivé et erreur.
5. Contrôler le réagencement à 320 px et les zooms navigateur à 200 % et 400 % sans perte d’information ni défilement bidimensionnel injustifié.

### Priorité moyenne

6. Vérifier la hiérarchie `h1` à `h6` sur chaque page, données réelles incluses.
7. Vérifier la pertinence des alternatives des photos téléversées et éviter de répéter un texte adjacent identique.
8. Tester les carrousels, filtres de produits et constructeurs dynamiques de recette sans souris et avec lecteur d’écran.
9. Tester le site avec JavaScript désactivé et vérifier qu’une solution de remplacement existe pour chaque fonction indispensable.
10. Contrôler l’affichage avec les couleurs forcées et les feuilles de style utilisateur.

### Obligations documentaires

11. Créer une page publique « Accessibilité » accessible depuis toutes les pages.
12. Publier une déclaration d’accessibilité seulement après l’audit final représentatif.
13. Prévoir un moyen de contact accessible et un schéma pluriannuel lorsque l’entité y est assujettie.

## 7. Résultats des vérifications techniques

| Vérification | Résultat |
|---|---|
| Syntaxe Twig | 32 fichiers valides |
| Syntaxe YAML | 30 fichiers valides |
| Conteneur Symfony | Valide |
| PHPUnit | 15 tests, 25 assertions réussies |
| HTML public — images sans `alt` | Aucune détectée sur l’échantillon |
| HTML public — champ sans nom accessible | Aucun détecté sur l’échantillon |
| HTML public — lien/bouton sans nom accessible | Aucun détecté sur l’échantillon |
| HTML public — identifiant dupliqué | Aucun détecté sur l’échantillon |
| Contrôle Git des espaces et fins de ligne | Réussi |

## 8. Conclusion

Le projet possède désormais une base d’accessibilité solide et plusieurs obstacles importants ont été corrigés. Cependant, un taux de conformité ne peut pas être calculé honnêtement tant que chaque critère applicable n’a pas reçu un statut **conforme**, **non conforme** ou **non applicable** sur un échantillon représentatif, avec les tests manuels requis.

Le statut conseillé avant cet audit final est donc :

> **Au fil des saisons est non conforme au RGAA 4.1.2, car aucun audit de conformité exhaustif n’a encore été achevé.**

## 9. Références

- Référentiel RGAA : <https://accessibilite.numerique.gouv.fr/methode/criteres-et-tests/>
- Méthodologie de test : <https://accessibilite.numerique.gouv.fr/ressources/methodologie-de-test/>
- Mentions et pages obligatoires : <https://accessibilite.numerique.gouv.fr/obligations/mentions-et-pages-obligatoires/>
