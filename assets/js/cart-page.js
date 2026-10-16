(() => {
    const storageKey = 'noir-form-cart';
    const catalog = JSON.parse(document.querySelector('#product-catalog').textContent);
    const products = new Map(catalog.map((product) => [String(product.id), product]));
    const itemsElement = document.querySelector('[data-bag-items]');
    const emptyMessage = document.querySelector('[data-empty-bag]');
    let cart = {};

    try {
        const savedCart = JSON.parse(localStorage.getItem(storageKey) || '{}');
        if (savedCart && typeof savedCart === 'object' && !Array.isArray(savedCart)) {
            Object.entries(savedCart).forEach(([key, quantity]) => {
                const [productId, savedVariantId] = key.split(':');
                const product = products.get(productId);
                const variantId = savedVariantId ?? String(product?.variants?.[0]?.id ?? 0);
                if (product && /^\d+$/.test(variantId)) cart[`${productId}:${variantId}`] = quantity;
            });
        }
    } catch (error) {
        console.warn('The saved shopping bag could not be read.', error);
    }

    const money = (cents) => `Rs ${new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(cents / 100)}`;
    const productImageUrl = (image, options) => /^uploads\/products\/[a-f0-9]{32}\.(jpg|png|webp)$/.test(image)
        ? image
        : `https://images.unsplash.com/${encodeURIComponent(image)}?auto=format&fit=crop&${options}`;

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

        Object.entries(cart).forEach(([key, rawQuantity]) => {
            const [id, variantId] = key.split(':');
            const product = products.get(id);
            const quantity = Number(rawQuantity);
            const variant = product?.variants?.find((option) => String(option.id) === variantId);
            if (!product || (variantId !== '0' && !variant) || !Number.isInteger(quantity) || quantity < 1 || quantity > 10) {
                delete cart[key];
                return;
            }

            const priceCents = Math.round(Number(product.price) * 100);
            subtotal += priceCents * quantity;
            itemCount += quantity;
            const article = document.createElement('article');
            article.className = 'bag-item';

            const image = document.createElement('img');
            image.src = productImageUrl(variant?.image ?? product.image, 'w=320&q=78');
            image.alt = product.name;

            const details = document.createElement('div');
            details.className = 'bag-item-details';
            const category = document.createElement('p');
            category.className = 'product-category';
            category.textContent = variant ? `${product.category} · ${variant.color_name}` : product.category;
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
            decrease.dataset.cartKey = key;
            decrease.setAttribute('aria-label', `Remove one ${product.name}${variant ? ` in ${variant.color_name}` : ''}`);
            decrease.textContent = '−';
            const count = document.createElement('span');
            count.textContent = String(quantity);
            const increase = document.createElement('button');
            increase.type = 'button';
            increase.dataset.action = 'increase';
            increase.dataset.cartKey = key;
            increase.setAttribute('aria-label', `Add one ${product.name}${variant ? ` in ${variant.color_name}` : ''}`);
            increase.disabled = quantity >= 10;
            increase.textContent = '+';
            controls.append(decrease, count, increase);

            const remove = document.createElement('button');
            remove.className = 'bag-item-remove';
            remove.type = 'button';
            remove.dataset.action = 'remove';
            remove.dataset.cartKey = key;
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
        const key = button.dataset.cartKey;
        const quantity = Number(cart[key]) || 0;

        if (button.dataset.action === 'remove' || (button.dataset.action === 'decrease' && quantity <= 1)) {
            delete cart[key];
        } else if (button.dataset.action === 'decrease') {
            cart[key] = quantity - 1;
        } else if (quantity < 10) {
            cart[key] = quantity + 1;
        }
        saveCart();
    });

    window.addEventListener('storage', (event) => {
        if (event.key !== storageKey) return;
        try {
            const savedCart = JSON.parse(event.newValue || '{}');
            cart = {};
            if (savedCart && typeof savedCart === 'object' && !Array.isArray(savedCart)) {
                Object.entries(savedCart).forEach(([key, quantity]) => {
                    const [productId, savedVariantId] = key.split(':');
                    const product = products.get(productId);
                    const variantId = savedVariantId ?? String(product?.variants?.[0]?.id ?? 0);
                    if (product && /^\d+$/.test(variantId)) cart[`${productId}:${variantId}`] = quantity;
                });
            }
        } catch (error) {
            console.warn('An updated shopping bag could not be read.', error);
            cart = {};
        }
        render();
    });

    render();
})();
