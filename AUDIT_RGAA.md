# Audit d’accessibilité RGAA 4.1.2

Date de l’audit technique initial : 3 septembre 2026.

## Périmètre contrôlé

- gabarit commun, en-tête, menu, pied de page et messages d’état ;
- accueil, connexion, création de compte et catalogue des produits rendus par Symfony ;
- formulaires de recette, commentaires et signalements examinés dans les gabarits Twig ;
- tableaux de produits, utilisateurs et notes ;
- feuilles de style communes et navigation au clavier du menu.

## Correctifs réalisés

- ajout d’un lien d’évitement vers le contenu principal ;
- ajout ou correction des noms accessibles des liens et boutons composés d’icônes ;
- masquage des icônes décoratives pour les technologies d’assistance ;
- association explicite des champs de commentaire, réponse et signalement à leurs étiquettes ;
- ajout de légendes et de portées d’en-têtes aux tableaux de données ;
- indication de la page courante dans la navigation ;
- focus clavier visible sur tous les composants interactifs ;
- suppression des déplacements de focus automatiques sur la connexion et la recherche ;
- prise en compte de `prefers-reduced-motion` ;
- renforcement du contraste de l’ambre commun : 5,76:1 sur le fond principal et 6,39:1 sur blanc ;
- amélioration du contraste des icônes du bandeau et du pied de page : 3,81:1 sur le vert principal.

## Contrôles automatisés effectués

- validation Twig des 32 gabarits ;
- validation des 30 fichiers YAML et du conteneur Symfony ;
- contrôle des pages publiques rendues : langue, alternative des images, noms accessibles des champs, liens et boutons, unicité des identifiants ;
- 7 tests PHPUnit, 17 assertions : succès ;
- contrôle des erreurs d’espaces et de fins de ligne Git : succès.

## Contrôles manuels restant indispensables

Un audit automatisé ne suffit pas à déclarer le site conforme au RGAA. Avant de publier une déclaration de conformité, il reste notamment à tester un échantillon représentatif complet :

- parcours intégral au clavier, ordre de tabulation et absence de piège au focus ;
- restitution avec NVDA/Firefox et VoiceOver/Safari ;
- zoom à 200 % et réagencement à 320 pixels de large ;
- contrastes de tous les états, images porteuses d’information et contenus ajoutés en base ;
- pertinence des titres, intitulés de liens et messages d’erreur en contexte ;
- affichage avec CSS ou JavaScript désactivé, ainsi que les éventuels médias et documents téléchargeables.

Le statut public doit donc rester **non audité complètement / non conforme par défaut** tant que ces vérifications manuelles et la déclaration d’accessibilité réglementaire ne sont pas terminées.
