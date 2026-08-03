document.addEventListener('DOMContentLoaded', () => {
    const categorySelect = document.querySelector('.js-product-category');
    const products = document.querySelectorAll('[data-category]');

    if (!categorySelect || products.length === 0) {
        return;
    }

    const filterProducts = () => {
        const selectedCategory = categorySelect.value;

        products.forEach((product) => {
            const choice = product.closest('.form-check') ?? product.parentElement;
            const isVisible = selectedCategory !== '' && product.dataset.category === selectedCategory;

            choice.hidden = !isVisible;
            product.disabled = !isVisible;

            if (!isVisible) {
                product.checked = false;
            }
        });
    };

    categorySelect.addEventListener('change', filterProducts);
    filterProducts();
});
