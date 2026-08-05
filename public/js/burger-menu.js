(() => {
    const initializeMenu = () => {
        const overlay = document.querySelector('[data-menu-overlay]');
        const openButton = document.querySelector('.js-menu-open');
        const closeButton = document.querySelector('.js-menu-close');
        const menu = document.querySelector('#site-menu');

        if (!overlay || !openButton || !closeButton || !menu || overlay.dataset.initialized) {
            return;
        }

        overlay.dataset.initialized = 'true';
        let lastFocusedElement = null;

        const focusableElements = () => [...menu.querySelectorAll('a[href], button:not([disabled]), summary, input, select')];

        const openMenu = () => {
            lastFocusedElement = document.activeElement;
            overlay.hidden = false;
            document.body.classList.add('menu-is-open');
            openButton.setAttribute('aria-expanded', 'true');
            requestAnimationFrame(() => {
                overlay.classList.add('is-open');
                closeButton.focus();
            });
        };

        const closeMenu = () => {
            overlay.classList.remove('is-open');
            document.body.classList.remove('menu-is-open');
            openButton.setAttribute('aria-expanded', 'false');
            window.setTimeout(() => {
                overlay.hidden = true;
                if (lastFocusedElement instanceof HTMLElement) {
                    lastFocusedElement.focus();
                }
            }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 250);
        };

        openButton.addEventListener('click', openMenu);
        closeButton.addEventListener('click', closeMenu);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (overlay.hidden) return;

            if (event.key === 'Escape') {
                closeMenu();
                return;
            }

            if (event.key === 'Tab') {
                const elements = focusableElements();
                const first = elements[0];
                const last = elements[elements.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
    };

    document.addEventListener('DOMContentLoaded', initializeMenu);
    document.addEventListener('turbo:load', initializeMenu);
})();
