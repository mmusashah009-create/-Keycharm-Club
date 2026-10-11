(() => {
    const storageKey = 'noir-form-cart';
    const catalog = JSON.parse(document.querySelector('#product-catalog').textContent);
    const products = new Map(catalog.map((product) => [String(product.id), product]));
    const itemsElement = document.querySelector('[data-bag-items]');
    const emptyMessage = document.querySelector('[data-empty-bag]');
    let cart = {};

    try {
        cart = JSON.parse(localStorage.getItem(storageKey) || '{}');
        if (!cart || typeof cart !== 'object' || Array.isArray(cart)) cart = {};
    } catch (error) {
        console.warn('The saved shopping bag could not be read.', error);
    }

    const money = (cents) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);

    const saveCart = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(cart));
        } catch (error) {
            console.warn('The shopping bag could not be saved in this browser.', error);
        }
        render();
    };

    const render = () => {
        itemsElement.replaceChildren();
        let subtotal = 0;
        let itemCount = 0;

        Object.entries(cart).forEach(([id, rawQuantity]) => {
            const product = products.get(id);
            const quantity = Number(rawQuantity);
            if (!product || !Number.isInteger(quantity) || quantity < 1 || quantity > 10) {
                delete cart[id];
                return;
            }

            const priceCents = Math.round(Number(product.price) * 100);
            subtotal += priceCents * quantity;
            itemCount += quantity;
            const article = document.createElement('article');
            article.className = 'bag-item';

            const image = document.createElement('img');
            image.src = `https://images.unsplash.com/${encodeURIComponent(product.image)}?auto=format&fit=crop&w=320&q=78`;
            image.alt = product.name;

            const details = document.createElement('div');
            details.className = 'bag-item-details';
            const category = document.createElement('p');
            category.className = 'product-category';
            category.textContent = product.category;
            const name = document.createElement('h2');
            name.textContent = product.name;
            const price = document.createElement('p');
            price.className = 'bag-item-price';
            price.textContent = money(priceCents);
            details.append(category, name, price);

            const controls = document.createElement('div');
            controls.className = 'cart-item-controls';
            const decrease = document.createElement('button');
            decrease.type = 'button';
            decrease.dataset.action = 'decrease';
            decrease.dataset.productId = id;
            decrease.setAttribute('aria-label', `Remove one ${product.name}`);
            decrease.textContent = '−';
            const count = document.createElement('span');
            count.textContent = String(quantity);
            const increase = document.createElement('button');
            increase.type = 'button';
            increase.dataset.action = 'increase';
            increase.dataset.productId = id;
            increase.setAttribute('aria-label', `Add one ${product.name}`);
            increase.disabled = quantity >= 10;
            increase.textContent = '+';
            controls.append(decrease, count, increase);

            const remove = document.createElement('button');
            remove.className = 'bag-item-remove';
            remove.type = 'button';
            remove.dataset.action = 'remove';
            remove.dataset.productId = id;
            remove.textContent = 'Remove';
            article.append(image, details, controls, remove);
            itemsElement.append(article);
        });

        const shippingCents = itemCount === 0 || subtotal >= 15000 ? 0 : 1200;
        const totalCents = subtotal + shippingCents;
        document.querySelector('[data-bag-header-count]').textContent = `${itemCount} ${itemCount === 1 ? 'ITEM' : 'ITEMS'}`;
        document.querySelector('[data-bag-summary]').textContent = `${itemCount} ${itemCount === 1 ? 'piece' : 'pieces'}`;
        document.querySelector('[data-bag-subtotal]').textContent = money(subtotal);
        document.querySelector('[data-bag-shipping]').textContent = itemCount === 0 ? '—' : (shippingCents === 0 ? 'Complimentary' : money(shippingCents));
        document.querySelector('[data-bag-total]').textContent = money(totalCents);
        emptyMessage.hidden = itemCount !== 0;
        document.querySelector('.order-summary').hidden = itemCount === 0;

        try {
            if (JSON.stringify(cart) !== localStorage.getItem(storageKey)) {
                localStorage.setItem(storageKey, JSON.stringify(cart));
            }
        } catch (error) {
            console.warn('The shopping bag could not be saved in this browser.', error);
        }
    };

    itemsElement.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const id = button.dataset.productId;
        const quantity = Number(cart[id]) || 0;

        if (button.dataset.action === 'remove' || (button.dataset.action === 'decrease' && quantity <= 1)) {
            delete cart[id];
        } else if (button.dataset.action === 'decrease') {
            cart[id] = quantity - 1;
        } else if (quantity < 10) {
            cart[id] = quantity + 1;
        }
        saveCart();
    });

    window.addEventListener('storage', (event) => {
        if (event.key !== storageKey) return;
        try {
            cart = JSON.parse(event.newValue || '{}');
            if (!cart || typeof cart !== 'object' || Array.isArray(cart)) cart = {};
        } catch (error) {
            console.warn('An updated shopping bag could not be read.', error);
            cart = {};
        }
        render();
    });

    render();
})();
