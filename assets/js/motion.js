(() => {
    const start = () => {
        if (document.visibilityState === 'hidden') {
            return;
        }

        document.removeEventListener('visibilitychange', start);

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
            return;
        }

        const selector = [
            '.hero-content',
            'main > section',
            '.product-card',
            '.bag-list',
            '.order-summary',
            '.page-title-row',
            '.checkout-form',
            '.confirmation-card',
            '.admin-page-heading',
            '.admin-flash',
            '.admin-stats',
            '.admin-panel',
            '.admin-product',
            '.auth-card'
        ].join(',');
        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -36px 0px' });

        const reveal = (root) => {
            if (root.nodeType !== Node.ELEMENT_NODE) {
                return;
            }

            const elements = [];
            if (root.matches(selector)) {
                elements.push(root);
            }
            elements.push(...root.querySelectorAll(selector));

            elements.forEach((element) => {
                if (element.classList.contains('motion-reveal')) {
                    return;
                }

                const siblings = Array.from(element.parentElement.children).filter((sibling) => sibling.matches(selector));
                element.style.setProperty('--motion-delay', `${(siblings.indexOf(element) % 5) * 65}ms`);
                element.classList.add('motion-reveal');
                observer.observe(element);
            });
        };

        document.body.classList.add('motion-ready');
        reveal(document.body);
        new MutationObserver((records) => {
            records.forEach((record) => {
                record.addedNodes.forEach(reveal);
            });
        }).observe(document.body, { childList: true, subtree: true });
    };

    if (document.visibilityState === 'hidden') {
        document.addEventListener('visibilitychange', start);
    } else {
        start();
    }
})();
