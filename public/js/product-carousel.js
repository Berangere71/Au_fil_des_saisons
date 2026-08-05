(() => {
    const initializeCarousels = () => {
        document.querySelectorAll('[data-product-carousel]').forEach((carousel) => {
            if (carousel.dataset.carouselInitialized) return;

            carousel.dataset.carouselInitialized = 'true';
            const section = carousel.closest('.season-category');
            const previous = section?.querySelector('[data-carousel-previous]');
            const next = section?.querySelector('[data-carousel-next]');
            let dragging = false;
            let moved = false;
            let suppressClickUntil = 0;
            let startX = 0;
            let startScrollLeft = 0;

            const updateButtons = () => {
                const maximum = carousel.scrollWidth - carousel.clientWidth;
                if (previous) previous.disabled = carousel.scrollLeft <= 2;
                if (next) next.disabled = maximum <= 2 || carousel.scrollLeft >= maximum - 2;
            };

            const scroll = (direction) => {
                carousel.scrollBy({ left: direction * Math.max(carousel.clientWidth * .75, 180), behavior: 'smooth' });
            };

            previous?.addEventListener('click', () => scroll(-1));
            next?.addEventListener('click', () => scroll(1));
            carousel.addEventListener('scroll', updateButtons, { passive: true });
            window.addEventListener('resize', updateButtons);

            carousel.addEventListener('pointerdown', (event) => {
                if ('mouse' !== event.pointerType || 0 !== event.button) return;
                dragging = true;
                moved = false;
                startX = event.clientX;
                startScrollLeft = carousel.scrollLeft;
            });

            carousel.addEventListener('pointermove', (event) => {
                if (!dragging) return;
                if (!moved && Math.abs(event.clientX - startX) > 8) {
                    moved = true;
                    carousel.classList.add('is-dragging');
                    carousel.setPointerCapture(event.pointerId);
                }
                if (!moved) return;
                carousel.scrollLeft = startScrollLeft - (event.clientX - startX);
            });

            const stopDragging = (event) => {
                if (!dragging) return;
                dragging = false;
                carousel.classList.remove('is-dragging');
                if (moved) suppressClickUntil = performance.now() + 350;
                if (carousel.hasPointerCapture(event.pointerId)) carousel.releasePointerCapture(event.pointerId);
                moved = false;
                updateButtons();
            };

            carousel.addEventListener('pointerup', stopDragging);
            carousel.addEventListener('pointercancel', stopDragging);
            carousel.addEventListener('dragstart', (event) => event.preventDefault());
            carousel.addEventListener('click', (event) => {
                if (performance.now() < suppressClickUntil) {
                    event.preventDefault();
                }
            }, true);
            carousel.addEventListener('keydown', (event) => {
                if ('ArrowLeft' === event.key || 'ArrowRight' === event.key) {
                    event.preventDefault();
                    scroll('ArrowLeft' === event.key ? -1 : 1);
                }
            });
            updateButtons();
        });
    };

    document.addEventListener('DOMContentLoaded', initializeCarousels);
    document.addEventListener('turbo:load', initializeCarousels);
})();
