document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.product-form');
    const nameInput = document.querySelector('#product_nom');
    const recipeContainer = document.querySelector('#product_recettes');
    const helperMessage = document.querySelector('.js-product-recipe-message');

    if (!form || !nameInput || !recipeContainer) {
        return;
    }

    const recipeInputs = [...recipeContainer.querySelectorAll('input[type="checkbox"][data-recipe-search]')];

    if (recipeInputs.length === 0) {
        return;
    }

    // Normalize each checkbox into a predictable wrapper for reliable filtering.
    recipeInputs.forEach((input) => {
        let choice = input.closest('.form-check');

        if (!choice) {
            choice = document.createElement('div');
            input.before(choice);
            choice.append(input);
            const label = recipeContainer.querySelector(`label[for="${input.id}"]`);
            if (label) {
                choice.append(label);
            }
        }

        choice.classList.add('product-recipe-choice');
    });

    const normalize = (value) => value.trim().toLocaleLowerCase('fr');

    const updateVisibleRecipes = () => {
        const query = normalize(nameInput.value);
        let visibleCount = 0;

        recipeInputs.forEach((input) => {
            const choice = input.closest('.product-recipe-choice');
            if (!choice) {
                return;
            }

            const searchText = normalize(input.dataset.recipeSearch || '');
            const shouldShow = query === '' || searchText.includes(query);
            choice.hidden = !shouldShow;
            if (shouldShow) {
                visibleCount += 1;
            }
        });

        if (!helperMessage) {
            return;
        }

        if (query === '') {
            helperMessage.textContent = 'Saisissez le nom du produit pour filtrer automatiquement les recettes associées.';
            return;
        }

        helperMessage.textContent = visibleCount > 0
            ? `${visibleCount} recette${visibleCount > 1 ? 's' : ''} correspond${visibleCount > 1 ? 'ent' : ''} à "${nameInput.value.trim()}".`
            : `Aucune recette trouvée pour "${nameInput.value.trim()}". Les recettes seront tout de même reliées automatiquement si le produit est détecté dans les ingrédients.`;
    };

    nameInput.addEventListener('input', updateVisibleRecipes);
    updateVisibleRecipes();
});
