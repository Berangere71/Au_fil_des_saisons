#!/usr/bin/env python3
"""Corrige et enrichit le dossier projet DOCX sans modifier l'original."""

from copy import deepcopy
from pathlib import Path
from tempfile import TemporaryDirectory
from zipfile import ZIP_DEFLATED, ZipFile
import xml.etree.ElementTree as ET


SOURCE = Path("/home/user/Téléchargements/AU FIL DES SAISONS/dossier_projet_au_fil_des_saisons.docx")
DESTINATION = Path("/home/user/Au_fil_des_saisons/dossier_projet_au_fil_des_saisons_corrige.docx")

W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
XML = "http://www.w3.org/XML/1998/namespace"
NS = {"w": W}
ET.register_namespace("w", W)


def qn(tag: str) -> str:
    return f"{{{W}}}{tag}"


def paragraph_text(paragraph: ET.Element) -> str:
    return "".join(node.text or "" for node in paragraph.findall(".//w:t", NS)).strip()


def replace_paragraph(paragraph: ET.Element, new_text: str) -> None:
    text_nodes = paragraph.findall(".//w:t", NS)
    if not text_nodes:
        run = ET.SubElement(paragraph, qn("r"))
        text_node = ET.SubElement(run, qn("t"))
        text_nodes = [text_node]
    text_nodes[0].text = new_text
    text_nodes[0].set(f"{{{XML}}}space", "preserve")
    for node in text_nodes[1:]:
        node.text = ""


def make_paragraph(text: str = "", style: str | None = None, code: bool = False) -> ET.Element:
    paragraph = ET.Element(qn("p"))
    p_pr = ET.SubElement(paragraph, qn("pPr"))
    if style:
        p_style = ET.SubElement(p_pr, qn("pStyle"))
        p_style.set(qn("val"), style)
    if code:
        spacing = ET.SubElement(p_pr, qn("spacing"))
        spacing.set(qn("before"), "0")
        spacing.set(qn("after"), "0")
        shading = ET.SubElement(p_pr, qn("shd"))
        shading.set(qn("fill"), "F3F4F6")
        ind = ET.SubElement(p_pr, qn("ind"))
        ind.set(qn("left"), "360")

    run = ET.SubElement(paragraph, qn("r"))
    if code:
        r_pr = ET.SubElement(run, qn("rPr"))
        fonts = ET.SubElement(r_pr, qn("rFonts"))
        fonts.set(qn("ascii"), "Consolas")
        fonts.set(qn("hAnsi"), "Consolas")
        size = ET.SubElement(r_pr, qn("sz"))
        size.set(qn("val"), "17")
    text_node = ET.SubElement(run, qn("t"))
    text_node.set(f"{{{XML}}}space", "preserve")
    text_node.text = text
    return paragraph


def page_break() -> ET.Element:
    paragraph = make_paragraph()
    run = paragraph.find("w:r", NS)
    br = ET.SubElement(run, qn("br"))
    br.set(qn("type"), "page")
    return paragraph


def add_code(body: ET.Element, code: str) -> None:
    for line in code.strip("\n").splitlines():
        body.insert(len(body) - 1, make_paragraph(line, code=True))


REPLACEMENTS = {
    "Version 1.0": "Version 1.1 — version corrigée et enrichie",
    "Les migrations Doctrine garantissent que le schéma de la base de données reste versionné et reproductible sur n'importe quel environnement. Des fixtures ont été créées afin de disposer d'un jeu de données de démonstration réaliste (produits, utilisateurs de test, recettes de saison).":
        "Les migrations Doctrine versionnent les évolutions du schéma de la base de données. Cinq classes de fixtures rendent l'environnement de démonstration reproductible : MonthFixtures crée les douze mois, SeasonFixtures les quatre saisons, UserFixtures un auteur de démonstration, ProductFixtures cent produits répartis en quatre catégories, et RecipeFixtures des recettes saisonnières. Les références Doctrine et DependentFixtureInterface imposent l'ordre de chargement et relient les objets sans identifiants écrits en dur. Le chargement s'effectue avec php bin/console doctrine:fixtures:load ; l'option --append permet de conserver les données déjà présentes, mais peut être évitée pour repartir d'un jeu propre.",
    "Après avoir renseigné le nom d'une ville, l'application interroge l'API OpenWeather, qui retourne une réponse au format JSON contenant la température et les conditions climatiques actuelles. Cette réponse est interprétée par le WeatherService, qui en extrait les informations utiles avant de les croiser avec le catalogue de produits et de recettes de saison afin de proposer une sélection contextualisée : une soupe par temps froid, un gratin en automne, un barbecue au printemps ou une salade en été.":
        "Après la saisie d'une ville, l'application interroge l'API OpenWeatherMap. WeatherService normalise la réponse JSON (ville, température, humidité, vent et description), gère les erreurs de transport ainsi que les codes 401 et 404, puis génère un conseil culinaire textuel selon la température et les conditions observées. Le croisement automatique avec les recettes enregistrées constitue une évolution prévue ; il n'est pas présenté ici comme une fonction déjà réalisée.",
    "Les mots de passe des utilisateurs sont hachés avec l'algorithme bcrypt avant tout stockage en base de données. Aucun mot de passe en clair ne transite ni n'est conservé, y compris dans les journaux applicatifs. Ce hachage est à sens unique : même en cas d'accès non autorisé à la base de données, les mots de passe originaux ne peuvent pas être retrouvés.":
        "Les mots de passe sont hachés par le PasswordHasher de Symfony avant leur stockage. La configuration utilise l'algorithme « auto », afin que Symfony choisisse un algorithme robuste pris en charge par l'environnement. Les fixtures emploient explicitement bcrypt pour le seul compte de démonstration. Aucun mot de passe en clair n'est enregistré en base de données.",
    "Une stratégie de tests a été mise en place afin de vérifier la fiabilité de l'application avant chaque nouvelle fonctionnalité importante.":
        "Une première base de tests automatisés vérifie le service météo. Elle devra être complétée par des tests fonctionnels sur les parcours critiques de l'application.",
    "Les parcours utilisateurs critiques (inscription, connexion, création et publication d'une recette) ont été testés à l'aide de WebTestCase, qui simule une requête HTTP complète et vérifie le contenu de la réponse.":
        "À la date de cette version, le dépôt ne contient pas encore de tests WebTestCase couvrant l'inscription, la connexion ou la création d'une recette. Leur ajout est identifié comme une priorité afin de sécuriser ces parcours lors des prochaines évolutions.",
    "En complément des tests automatisés, des sessions de tests utilisateurs informels ont été menées auprès de proches correspondant aux personas définis, afin de vérifier la clarté de la navigation et l'ergonomie générale de l'application.":
        "Des vérifications manuelles de navigation et d'ergonomie complètent les tests automatisés. Un protocole formalisé (scénarios, profils, résultats et corrections) pourra être ajouté lorsque des sessions utilisateurs documentées auront été réalisées.",
    "Chaque formulaire a été testé avec des données invalides (champs vides, formats incorrects) afin de vérifier que les messages d'erreur s'affichent correctement et qu'aucune donnée invalide n'est enregistrée en base.":
        "Les contraintes Symfony Validator et les contrôles de formulaire empêchent l'enregistrement des saisies invalides. Des tests automatisés dédiés aux cas limites restent à ajouter pour compléter les vérifications manuelles.",
    "Des vérifications manuelles ont été réalisées pour s'assurer qu'un utilisateur non connecté ne peut pas accéder aux pages réservées, et qu'un utilisateur non administrateur ne peut pas accéder à l'espace d'administration en modifiant directement l'URL.":
        "La configuration access_control protège les chemins /admin, /recettes et /profile selon les rôles requis. Des tests fonctionnels d'autorisation restent à automatiser afin de prévenir toute régression.",
    "Il renseigne le titre, les ingrédients, les étapes de préparation, une image, ainsi que les produits et la saison associés.":
        "Il renseigne le titre, les ingrédients, les étapes de préparation, une image et les produits associés. Les saisons sont ensuite déduites automatiquement des produits sélectionnés.",
    "La recette est enregistrée en base de données, puis l'utilisateur est redirigé vers sa fiche recette.":
        "Après validation, la recette et ses relations sont enregistrées par Doctrine, puis l'utilisateur est redirigé vers la liste des recettes.",
    "Le RecetteController reçoit la requête, instancie le formulaire associé à une nouvelle entité Recette, et le traite grâce à la méthode handleRequest.":
        "RecetteController crée une entité Recette liée à l'utilisateur connecté, lui attribue initialement le statut ATTENTE, puis délègue le traitement à processForm(), qui appelle handleRequest(), contrôle isSubmitted() et isValid(), gère l'image et synchronise les saisons.",
    "Le RecetteType définit les champs du formulaire ainsi que leurs contraintes de validation (titre obligatoire, longueur maximale, produits et saison requis).":
        "RecetteType définit les champs du formulaire et s'appuie sur les contraintes portées par l'entité. Les produits sont choisis par l'utilisateur ; la saison principale et la collection de saisons sont calculées dans le contrôleur à partir de ces produits.",
    "Les principaux extraits de code présentés dans ce dossier (entités Doctrine, contrôleurs, services, configuration de sécurité) illustrent les choix d'implémentation détaillés au fil des chapitres 9, 10 et 11.":
        "Les principaux extraits de code présentés dans ce dossier illustrent les choix d'implémentation. L'annexe I complète cette sélection avec des extraits commentés et vérifiés dans la version actuelle du dépôt : dépendances entre fixtures, relations entre entités, traitement d'un formulaire, service météo et test unitaire.",
    "et de Doctrine ORM a demandé un temps d'apprentissage, en particulier sur la gestion des migrations : plusieurs erreurs en cascade sont survenues lorsque les entités étaient modifiées sans générer de nouvelle migration correspondante.":
        "Symfony et de Doctrine ORM a demandé un temps d'apprentissage, en particulier sur la gestion des migrations : plusieurs erreurs en cascade sont survenues lorsque les entités étaient modifiées sans générer de nouvelle migration correspondante.",
}


def main() -> None:
    if not SOURCE.exists():
        raise SystemExit(f"Document source introuvable : {SOURCE}")

    with TemporaryDirectory() as temporary_directory:
        temporary = Path(temporary_directory)
        with ZipFile(SOURCE) as archive:
            archive.extractall(temporary)

        document_path = temporary / "word" / "document.xml"
        tree = ET.parse(document_path)
        root = tree.getroot()
        body = root.find("w:body", NS)
        if body is None:
            raise RuntimeError("Corps du document introuvable")

        found = set()
        paragraphs = body.findall(".//w:p", NS)
        for paragraph in paragraphs:
            current = paragraph_text(paragraph)
            if current in REPLACEMENTS:
                replace_paragraph(paragraph, REPLACEMENTS[current])
                found.add(current)

        missing = set(REPLACEMENTS) - found
        if missing:
            print("Avertissement : paragraphes non remplacés :")
            for text in sorted(missing):
                print(" -", text[:100])

        body.insert(len(body) - 1, page_break())
        body.insert(len(body) - 1, make_paragraph("Annexe I — Fixtures et extraits de code commentés", "Heading2"))
        body.insert(len(body) - 1, make_paragraph(
            "Les extraits suivants proviennent de la version actuelle de l'application. Ils sont volontairement raccourcis et commentés pour mettre en évidence le rôle de chaque instruction."
        ))

        body.insert(len(body) - 1, make_paragraph("I.1 — Ordre de chargement des fixtures", "Heading3"))
        body.insert(len(body) - 1, make_paragraph(
            "ProductFixtures dépend des mois et des saisons. Doctrine charge donc les référentiels avant de créer les produits. Les références permettent ensuite de relier les entités sans connaître leurs identifiants en base."
        ))
        add_code(body, """final class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        // Ces fixtures doivent être chargées avant les produits.
        return [MonthFixtures::class, SeasonFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $product->setDebutRecolteMois(
            // Récupération de l'objet Month déjà créé, sans ID en dur.
            $this->getReference('month_'.$startMonth, Month::class)
        );
        $manager->persist($product); // Planifie l'insertion.
        $manager->flush();           // Exécute les écritures en base.
    }
}""")

        body.insert(len(body) - 1, make_paragraph("I.2 — Calcul des saisons d'un produit", "Heading3"))
        body.insert(len(body) - 1, make_paragraph(
            "La boucle gère aussi les périodes qui traversent le changement d'année, par exemple de novembre à mars. L'intersection avec les mois de chaque saison détermine les associations à créer."
        ))
        add_code(body, """$month = $startMonth;
while (true) {
    $activeMonths[] = $month;
    if ($month === $endMonth) {
        break; // Toute la période a été parcourue.
    }
    // Après décembre, on revient à janvier.
    $month = 12 === $month ? 1 : $month + 1;
}

return array_values(array_filter(
    SeasonName::cases(),
    // Une saison est retenue si elle partage au moins un mois actif.
    fn (SeasonName $season) => [] !== array_intersect(
        $activeMonths,
        $seasonMonths[$season->value]
    )
));""")

        body.insert(len(body) - 1, make_paragraph("I.3 — Création des recettes de démonstration", "Heading3"))
        body.insert(len(body) - 1, make_paragraph(
            "RecipeFixtures dépend des produits et de l'utilisateur de démonstration. Chaque recette reçoit ses produits, hérite de leurs saisons et possède une saison principale. La recherche préalable rend le chargement réexécutable sans dupliquer les recettes."
        ))
        add_code(body, """$author = $this->getReference(
    UserFixtures::RECIPE_AUTHOR_REFERENCE,
    User::class
);

$recipe = $manager->getRepository(Recette::class)->findOneBy([
    'titre' => $title,
    'user' => $author,
]);

if (!$recipe instanceof Recette) {
    // Création uniquement si la recette n'existe pas déjà.
    $recipe = (new Recette())->setTitre($title)->setUser($author);
    $manager->persist($recipe);
}

foreach ($productNames as $productName) {
    $product = $this->getReference('product_'.$productName, Product::class);
    $recipe->addProduct($product); // Relation ManyToMany.
    foreach ($product->getSeasons() as $season) {
        $recipe->addSeason($season);
    }
}""")

        body.insert(len(body) - 1, make_paragraph("I.4 — Traitement du formulaire de recette", "Heading3"))
        body.insert(len(body) - 1, make_paragraph(
            "Le contrôleur ne persiste les données que lorsque le formulaire est soumis et valide. Le statut dépend du rôle et du choix de publication ; les saisons sont recalculées avant l'écriture en base."
        ))
        add_code(body, """$form = $this->createForm(RecetteType::class, $recette);
$form->handleRequest($request); // Hydrate l'entité avec la requête HTTP.

if ($form->isSubmitted() && $form->isValid()) {
    if ($this->isGranted('ROLE_ADMIN')) {
        $recette->setIsPublic(true)
                ->setStatut(RecetteStatut::PUBLIEE);
    } elseif ($recette->isPublic()) {
        $recette->setStatut(RecetteStatut::PUBLIEE);
    } else {
        $recette->setStatut(RecetteStatut::ATTENTE);
    }

    // Les saisons proviennent des produits choisis.
    $this->synchronizeSeasonsFromProducts($recette);
    $entityManager->persist($recette);
    $entityManager->flush();
}""")

        body.insert(len(body) - 1, make_paragraph("I.5 — Appel sécurisé au service météo", "Heading3"))
        body.insert(len(body) - 1, make_paragraph(
            "La clé d'API est injectée par configuration et n'apparaît pas dans le code. Le client HTTP transmet les paramètres, limite le temps d'attente et transforme les erreurs en messages compréhensibles."
        ))
        add_code(body, """$response = $this->httpClient->request('GET', self::ENDPOINT, [
    'query' => [
        'q' => trim($city),
        'appid' => $this->openWeatherApiKey, // Variable d'environnement.
        'units' => 'metric',
        'lang' => 'fr',
    ],
    'timeout' => 8,
]);

if (404 === $response->getStatusCode()) {
    // L'utilisateur reçoit un message métier, pas une erreur technique brute.
    throw new RuntimeException('Cette ville est introuvable.');
}""")

        body.insert(len(body) - 1, make_paragraph("I.6 — Test unitaire du service météo", "Heading3"))
        body.insert(len(body) - 1, make_paragraph(
            "MockHttpClient remplace l'appel réseau par une réponse maîtrisée. Le test vérifie ainsi la normalisation de la température, la conversion du vent en km/h et le conseil généré, sans dépendre de l'API externe."
        ))
        add_code(body, """$client = new MockHttpClient(new MockResponse(json_encode([
    'name' => 'Lyon',
    'main' => ['temp' => 27.4, 'humidity' => 61],
    'wind' => ['speed' => 3.5],
    'weather' => [['main' => 'Clear', 'description' => 'ciel dégagé']],
], JSON_THROW_ON_ERROR)));

$weather = (new WeatherService($client, 'test-key'))
    ->getCurrentWeather('Lyon');

self::assertSame(27, $weather['temperature']);
self::assertSame(12.6, $weather['wind']); // 3,5 m/s × 3,6.
self::assertStringContainsString('salade fraîche', $weather['suggestion']);""")

        body.insert(len(body) - 1, make_paragraph("I.7 — Commandes de reproduction du jeu de démonstration", "Heading3"))
        add_code(body, """# Mettre le schéma à jour à partir des migrations versionnées
php bin/console doctrine:migrations:migrate --no-interaction

# Recréer la base de démonstration et charger toutes les fixtures
php bin/console doctrine:fixtures:load --no-interaction

# Variante : conserver les données existantes
php bin/console doctrine:fixtures:load --append --no-interaction

# Exécuter les tests automatisés
php bin/phpunit""")

        tree.write(document_path, encoding="UTF-8", xml_declaration=True)

        # Demande à Word/LibreOffice de recalculer la table des matières à l'ouverture.
        settings_path = temporary / "word" / "settings.xml"
        settings_tree = ET.parse(settings_path)
        settings_root = settings_tree.getroot()
        update_fields = settings_root.find("w:updateFields", NS)
        if update_fields is None:
            update_fields = ET.SubElement(settings_root, qn("updateFields"))
        update_fields.set(qn("val"), "true")
        settings_tree.write(settings_path, encoding="UTF-8", xml_declaration=True)

        with ZipFile(DESTINATION, "w", ZIP_DEFLATED) as archive:
            for path in temporary.rglob("*"):
                if path.is_file():
                    archive.write(path, path.relative_to(temporary).as_posix())

    print(f"Document créé : {DESTINATION}")


if __name__ == "__main__":
    main()
