document.addEventListener('DOMContentLoaded', () => {
    const categorySelect = document.querySelector('.js-product-category');
    const products = document.querySelectorAll('[data-category]');
    const searchField = document.querySelector('#recette-product-query');
    const searchContainer = document.querySelector('.recette-product-search');
    const productList = document.querySelector('.recette-product-list');
    const message = document.querySelector('.recette-product-message');
    const selectedCount = document.querySelector('.js-selected-product-count');

    if (!categorySelect || products.length === 0) {
        return;
    }

    const productContainer = document.querySelector('#recette_products');

    // Symfony peut produire une structure légèrement différente selon le thème
    // de formulaire. On normalise donc chaque option en une ligne explicite.
    products.forEach((product) => {
        const label = document.querySelector(`label[for="${product.id}"]`);
        let choice = product.closest('.form-check');

        if (!choice && product.parentElement !== productContainer) {
            choice = product.parentElement;
        }

        if (!choice || choice === productContainer || (label && !choice.contains(label))) {
            choice = document.createElement('div');
            product.before(choice);
            choice.append(product);
            if (label) choice.append(label);
        }

        choice.classList.add('recette-product-choice');
        choice.dataset.productCategory = product.dataset.category.trim().toLocaleLowerCase('fr');
    });

    const getChoice = (product) => product.closest('.recette-product-choice');

    const updateSelectedCount = () => {
        const count = [...products].filter((product) => product.checked).length;

        if (selectedCount) {
            selectedCount.textContent = `${count} produit${count > 1 ? 's' : ''} sélectionné${count > 1 ? 's' : ''}`;
        }
    };

    const filterProducts = () => {
        const selectedCategory = categorySelect.value.trim().toLocaleLowerCase('fr');
        const query = searchField?.value.trim().toLocaleLowerCase('fr') ?? '';
        let visibleCount = 0;

        products.forEach((product) => {
            const choice = getChoice(product);
            const label = choice.querySelector('label')?.textContent.trim().toLocaleLowerCase('fr') ?? '';
            const matchesCategory = selectedCategory === 'all' || (
                selectedCategory !== '' && choice.dataset.productCategory === selectedCategory
            );
            const matchesSearch = query === '' || label.includes(query);
            const isVisible = matchesCategory && matchesSearch;

            choice.hidden = !isVisible;
            visibleCount += isVisible ? 1 : 0;

            // Un filtre ne doit jamais modifier la sélection de l'utilisateur.
            // Les cases masquées restent actives et seront bien envoyées au serveur.
            product.disabled = false;
        });

        const hasCategory = selectedCategory !== '';
        if (searchContainer) searchContainer.hidden = !hasCategory;
        if (productList) productList.hidden = !hasCategory;
        if (message) {
            message.hidden = hasCategory && visibleCount > 0;
            message.textContent = hasCategory
                ? 'Aucun produit ne correspond à cette recherche.'
                : 'Choisissez une catégorie pour afficher les produits.';
        }
    };

    // En modification, ouvrir automatiquement la catégorie du premier produit
    // déjà associé à la recette afin que la sélection existante soit visible.
    const firstSelectedProduct = [...products].find((product) => product.checked);
    if (firstSelectedProduct && categorySelect.value === '') {
        categorySelect.value = firstSelectedProduct.dataset.category;
    }

    categorySelect.addEventListener('change', () => {
        if (searchField) searchField.value = '';
        filterProducts();
    });
    searchField?.addEventListener('input', filterProducts);
    products.forEach((product) => product.addEventListener('change', updateSelectedCount));
    filterProducts();
    updateSelectedCount();
});
