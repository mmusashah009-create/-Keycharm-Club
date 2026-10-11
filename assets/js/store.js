(() => {
    const cards = [...document.querySelectorAll('.product-card')];
    const filterButtons = [...document.querySelectorAll('[data-filter]')];
    const searchPanel = document.querySelector('.search-panel');
    const searchInput = document.querySelector('#site-search');
    const toast = document.querySelector('.toast');
    const cartCount = document.querySelector('[data-cart-count]');
    const cartButton = document.querySelector('[data-open-cart]');
    const cartDrawer = document.querySelector('.cart-drawer');
    const cartItems = document.querySelector('[data-cart-items]');
    const cartEmpty = document.querySelector('[data-cart-empty]');
    const emptyResults = document.querySelector('.empty-results');
    const menuToggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('#primary-navigation');
    let activeCategory = 'All pieces';
    let toastTimer;
    let cart = {};

    try {
        cart = JSON.parse(localStorage.getItem('noir-form-cart') || '{}');
        if (!cart || typeof cart !== 'object' || Array.isArray(cart)) cart = {};
    } catch (error) {
        console.warn('The saved shopping bag could not be read.', error);
    }

    const totalItems = () => Object.values(cart).reduce((total, quantity) => total + (Number(quantity) || 0), 0);

    const updateCartCount = () => {
        const count = totalItems();
        cartCount.textContent = String(count);
        cartButton.setAttribute('aria-label', `Shopping bag, ${count} ${count === 1 ? 'item' : 'items'}`);
        document.querySelector('[data-cart-heading-count]').textContent = `(${count})`;
    };

    const renderCart = () => {
        cartItems.replaceChildren();
        let subtotal = 0;

        Object.entries(cart).forEach(([productId, quantity]) => {
            const card = cards.find((item) => item.querySelector('[data-add-to-cart]').dataset.addToCart === productId);
            const count = Number(quantity);
            if (!card || !Number.isInteger(count) || count <= 0) return;

            const price = Number(card.dataset.price);
            subtotal += price * count;
            const row = document.createElement('article');
            row.className = 'cart-item';

            const image = document.createElement('img');
            image.src = card.querySelector('.product-image-wrap img').src;
            image.alt = '';

            const details = document.createElement('div');
            details.className = 'cart-item-details';
            const name = document.createElement('h3');
            name.textContent = card.dataset.name;
            const category = document.createElement('p');
            category.textContent = card.dataset.category;
            const priceLabel = document.createElement('p');
            priceLabel.className = 'cart-item-price';
            priceLabel.textContent = `$${price.toFixed(0)}`;

            const controls = document.createElement('div');
            controls.className = 'cart-item-controls';
            const decrease = document.createElement('button');
            decrease.type = 'button';
            decrease.dataset.cartAction = 'decrease';
            decrease.dataset.productId = productId;
            decrease.setAttribute('aria-label', `Remove one ${card.dataset.name}`);
            decrease.textContent = '−';
            const quantityLabel = document.createElement('span');
            quantityLabel.textContent = String(count);
            const increase = document.createElement('button');
            increase.type = 'button';
            increase.dataset.cartAction = 'increase';
            increase.dataset.productId = productId;
            increase.setAttribute('aria-label', `Add one ${card.dataset.name}`);
            increase.textContent = '+';
            controls.append(decrease, quantityLabel, increase);
            details.append(name, category, priceLabel, controls);

            const remove = document.createElement('button');
            remove.className = 'cart-item-remove';
            remove.type = 'button';
            remove.dataset.cartAction = 'remove';
            remove.dataset.productId = productId;
            remove.textContent = 'Remove';
            row.append(image, details, remove);
            cartItems.append(row);
        });

        const count = totalItems();
        cartEmpty.hidden = count !== 0;
        document.querySelector('.cart-footer').hidden = count === 0;
        document.querySelector('[data-cart-subtotal]').textContent = `$${subtotal.toFixed(0)}`;
        updateCartCount();
    };

    const saveCart = () => {
        try {
            localStorage.setItem('noir-form-cart', JSON.stringify(cart));
        } catch (error) {
            console.warn('The shopping bag could not be saved in this browser.', error);
        }
        renderCart();
    };

    const closeCart = () => {
        cartDrawer.classList.remove('open');
        cartDrawer.setAttribute('aria-hidden', 'true');
        cartDrawer.setAttribute('inert', '');
        document.querySelector('.cart-backdrop').hidden = true;
        document.body.classList.remove('cart-open');
        cartButton.focus();
    };

    const showToast = (message) => {
        toast.textContent = message;
        toast.classList.add('visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('visible'), 2400);
    };

    const applyFilters = () => {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;
        cards.forEach((card) => {
            const categoryMatches = activeCategory === 'All pieces' || card.dataset.category === activeCategory;
            const searchMatches = !query || card.dataset.name.toLowerCase().includes(query);
            card.hidden = !(categoryMatches && searchMatches);
            if (!card.hidden) visibleCount++;
        });
        emptyResults.hidden = visibleCount !== 0;
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.filter;
            filterButtons.forEach((item) => {
                const active = item === button;
                item.classList.toggle('active', active);
                item.setAttribute('aria-pressed', String(active));
            });
            applyFilters();
        });
    });

    document.querySelectorAll('[data-category-link]').forEach((link) => {
        link.addEventListener('click', () => {
            const button = filterButtons.find((item) => item.dataset.filter === link.dataset.categoryLink);
            if (button) button.click();
            navigation.classList.remove('open');
            menuToggle.setAttribute('aria-expanded', 'false');
        });
    });

    document.querySelector('.search-toggle').addEventListener('click', () => {
        searchPanel.classList.toggle('open');
        if (searchPanel.classList.contains('open')) searchInput.focus();
        else {
            searchInput.value = '';
            applyFilters();
        }
    });

    document.querySelector('.search-close').addEventListener('click', () => {
        searchPanel.classList.remove('open');
        searchInput.value = '';
        applyFilters();
    });
    searchInput.addEventListener('input', applyFilters);
    document.querySelector('.search-panel').addEventListener('submit', (event) => event.preventDefault());

    menuToggle.addEventListener('click', () => {
        const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!expanded));
        navigation.classList.toggle('open', !expanded);
    });

    document.querySelectorAll('[data-add-to-cart]').forEach((button) => {
        button.addEventListener('click', () => {
            const productId = button.dataset.addToCart;
            const quantity = Number(cart[productId]) || 0;
            if (quantity >= 10) {
                showToast('The maximum quantity for each piece is 10.');
                return;
            }
            cart[productId] = quantity + 1;
            saveCart();
            showToast('Added to your bag. Good choice.');
        });
    });

    cartButton.addEventListener('click', () => {
        renderCart();
        cartDrawer.classList.add('open');
        cartDrawer.setAttribute('aria-hidden', 'false');
        cartDrawer.removeAttribute('inert');
        document.querySelector('.cart-backdrop').hidden = false;
        document.body.classList.add('cart-open');
        cartDrawer.querySelector('.cart-close').focus();
    });

    document.querySelectorAll('[data-close-cart]').forEach((button) => button.addEventListener('click', closeCart));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && cartDrawer.classList.contains('open')) {
            closeCart();
            return;
        }
        if (event.key === 'Tab' && cartDrawer.classList.contains('open')) {
            const focusable = [...cartDrawer.querySelectorAll('a[href], button:not(:disabled)')];
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    cartItems.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cart-action]');
        if (!button) return;
        const productId = button.dataset.productId;
        const current = Number(cart[productId]) || 0;
        if (button.dataset.cartAction === 'remove' || (button.dataset.cartAction === 'decrease' && current <= 1)) {
            delete cart[productId];
        } else if (button.dataset.cartAction === 'decrease') {
            cart[productId] = current - 1;
        } else if (current < 10) {
            cart[productId] = current + 1;
        } else {
            showToast('The maximum quantity for each piece is 10.');
        }
        saveCart();
    });

    document.querySelector('[data-drawer-checkout]').addEventListener('click', (event) => {
        if (totalItems() === 0) {
            event.preventDefault();
            showToast('Add a piece to your bag before checkout.');
            return;
        }
        closeCart();
    });

    document.querySelectorAll('.favorite-button').forEach((button) => {
        button.addEventListener('click', () => {
            const saved = button.getAttribute('aria-pressed') !== 'true';
            button.setAttribute('aria-pressed', String(saved));
            button.classList.toggle('saved', saved);
            showToast(saved ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
        });
    });

    document.querySelector('[data-sort-toggle]').addEventListener('click', (event) => {
        const button = event.currentTarget;
        const ascending = button.dataset.sort !== 'price-low';
        button.dataset.sort = ascending ? 'price-low' : 'featured';
        button.firstChild.textContent = ascending ? 'Price: low to high ' : 'Featured ';
        const grid = document.querySelector('#product-grid');
        [...grid.children].sort((a, b) => {
            const difference = Number(a.dataset.price) - Number(b.dataset.price);
            return ascending ? difference : Number(a.dataset.originalOrder || (a.dataset.originalOrder = String(cards.indexOf(a)))) - Number(b.dataset.originalOrder || (b.dataset.originalOrder = String(cards.indexOf(b))));
        }).forEach((card) => grid.append(card));
    });

    document.querySelector('.newsletter-form').addEventListener('submit', (event) => {
        event.preventDefault();
        const message = document.querySelector('.newsletter-message');
        message.textContent = 'You’re on the list. Watch your inbox for something good.';
        message.classList.add('success');
        event.currentTarget.reset();
    });

    renderCart();
})();
