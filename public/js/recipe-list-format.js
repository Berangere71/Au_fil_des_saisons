document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.recette-form');
    const ingredientStorage = document.querySelector('.js-ingredient-storage');
    const preparationStorage = document.querySelector('.js-preparation-storage');
    const ingredientBuilder = document.querySelector('.js-ingredient-builder');
    const preparationBuilder = document.querySelector('.js-preparation-builder');

    if (!form || !ingredientStorage || !preparationStorage || !ingredientBuilder || !preparationBuilder) return;

    const units = [
        ['', 'Sans unité'], ['gr', 'gr'], ['unité', 'unité'],
        ['cc', 'cc (cuillère à café)'], ['cs', 'cs (cuillère à soupe)'],
        ['pincée', 'pincée'], ['litre', 'litre'], ['dcl', 'dcl'],
    ];
    const escapeHtml = (value) => {
        const element = document.createElement('div');
        element.textContent = value;
        return element.innerHTML;
    };
    const removeButton = '<button class="recipe-builder__remove" type="button" aria-label="Supprimer"><i class="fa-solid fa-trash"></i></button>';

    function addIngredient(name = '', quantity = '', unit = '') {
        const row = document.createElement('div');
        row.className = 'recipe-builder__row recipe-builder__row--ingredient';
        const options = units.map(([value, label]) => `<option value="${value}"${value === unit ? ' selected' : ''}>${label}</option>`).join('');
        row.innerHTML = `<input class="js-ingredient-name" type="text" required placeholder="Ex. Fraises" value="${escapeHtml(name)}"><span class="recipe-builder__slash">/</span><input class="js-ingredient-quantity" type="number" min="0" step="any" placeholder="Ex. 250" value="${escapeHtml(quantity)}"><select class="js-ingredient-unit" aria-label="Unité">${options}</select>${removeButton}`;
        ingredientBuilder.querySelector('.recipe-builder__rows').append(row);
    }

    function renumberSteps() {
        preparationBuilder.querySelectorAll('.recipe-builder__number').forEach((number, index) => {
            number.textContent = `${index + 1}.`;
        });
    }

    function addStep(text = '') {
        const row = document.createElement('div');
        row.className = 'recipe-builder__row recipe-builder__row--step';
        row.innerHTML = `<span class="recipe-builder__number"></span><textarea class="js-preparation-step" required rows="2" placeholder="Décrivez cette étape">${escapeHtml(text)}</textarea>${removeButton}`;
        preparationBuilder.querySelector('.recipe-builder__rows').append(row);
        renumberSteps();
    }

    ingredientStorage.value.split('\n').map((line) => line.replace(/^\s*[-•]\s*/, '').trim()).filter(Boolean).forEach((line) => {
        const match = line.match(/^(.*?)\s+—\s+([0-9.,]+)?\s*(.*)$/);
        addIngredient(match ? match[1].trim() : line, match?.[2]?.replace(',', '.') || '', match?.[3]?.trim() || '');
    });
    preparationStorage.value.split('\n').map((line) => line.replace(/^\s*\d+[.)]\s*/, '').trim()).filter(Boolean).forEach(addStep);

    if (!ingredientBuilder.querySelector('.recipe-builder__row')) addIngredient();
    if (!preparationBuilder.querySelector('.recipe-builder__row')) addStep();

    ingredientBuilder.querySelector('.recipe-builder__add').addEventListener('click', () => addIngredient());
    preparationBuilder.querySelector('.recipe-builder__add').addEventListener('click', () => addStep());
    document.addEventListener('click', (event) => {
        const button = event.target.closest('.recipe-builder__remove');
        if (!button) return;
        const builder = button.closest('.recipe-builder');
        button.closest('.recipe-builder__row').remove();
        if (builder === preparationBuilder) renumberSteps();
    });

    form.addEventListener('submit', () => {
        ingredientStorage.value = [...ingredientBuilder.querySelectorAll('.recipe-builder__row')].map((row) => {
            const name = row.querySelector('.js-ingredient-name').value.trim();
            const quantity = row.querySelector('.js-ingredient-quantity').value.trim();
            const unit = row.querySelector('.js-ingredient-unit').value;
            return name ? `• ${name}${quantity || unit ? ` — ${quantity}${quantity && unit ? ' ' : ''}${unit}` : ''}` : '';
        }).filter(Boolean).join('\n');
        preparationStorage.value = [...preparationBuilder.querySelectorAll('.js-preparation-step')]
            .map((step, index) => {
                const text = step.value.trim().replace(/\s*\n+\s*/g, ' ');
                return text ? `${index + 1}. ${text}` : '';
            })
            .filter(Boolean).join('\n');
    });
});
