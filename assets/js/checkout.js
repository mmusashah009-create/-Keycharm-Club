(() => {
    const storageKey = 'noir-form-cart';
    const catalog = JSON.parse(document.querySelector('#product-catalog').textContent);
    const products = new Map(catalog.map((product) => [String(product.id), product]));
    const itemList = document.querySelector('[data-checkout-items]');
    const form = document.querySelector('#checkout-form');
    const submitButton = form.querySelector('[type="submit"]');
    const errorMessage = document.querySelector('[data-checkout-error]');
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
    const cartKey = (productId, variantId = 0) => `${productId}:${variantId}`;
    const orderLines = [];
    let subtotal = 0;

    Object.entries(cart).forEach(([key, rawQuantity]) => {
        const [id, variantId] = key.split(':');
        const product = products.get(id);
        const quantity = Number(rawQuantity);
        const variant = product?.variants?.find((option) => String(option.id) === variantId);
        if (!product || (variantId !== '0' && !variant) || !Number.isInteger(quantity) || quantity < 1 || quantity > 10) {
            delete cart[key];
            return;
        }
        const unitPriceCents = Math.round(Number(product.price) * 100);
        subtotal += unitPriceCents * quantity;
        orderLines.push({ product, variant, quantity, unitPriceCents });
    });

    const count = orderLines.reduce((sum, line) => sum + line.quantity, 0);
    const shippingCents = count === 0 || subtotal >= 15000 ? 0 : 1200;
    const totalCents = subtotal + shippingCents;
    document.querySelector('[data-checkout-count]').textContent = count ? `(${count})` : '';
    document.querySelector('[data-checkout-subtotal]').textContent = money(subtotal);
    document.querySelector('[data-checkout-shipping]').textContent = count === 0 ? '—' : (shippingCents === 0 ? 'Complimentary' : money(shippingCents));
    document.querySelector('[data-checkout-total]').textContent = money(totalCents);

    orderLines.forEach(({ product, variant, quantity, unitPriceCents }) => {
        const row = document.createElement('div');
        row.className = 'checkout-item';
        const image = document.createElement('img');
        image.src = productImageUrl(variant?.image ?? product.image, 'w=180&q=76');
        image.alt = '';
        const details = document.createElement('div');
        const name = document.createElement('p');
        name.textContent = variant ? `${product.name} · ${variant.color_name}` : product.name;
        const quantityLabel = document.createElement('span');
        quantityLabel.textContent = `Qty ${quantity}`;
        details.append(name, quantityLabel);
        const price = document.createElement('span');
        price.textContent = money(unitPriceCents * quantity);
        row.append(image, details, price);
        itemList.append(row);
    });

    try {
        localStorage.setItem(storageKey, JSON.stringify(cart));
    } catch (error) {
        console.warn('The shopping bag could not be saved in this browser.', error);
    }

    if (count === 0) {
        errorMessage.hidden = false;
        errorMessage.textContent = 'Your bag is empty. Add a piece before checking out.';
        submitButton.disabled = true;
        const backLink = document.createElement('a');
        backLink.className = 'checkout-link';
        backLink.href = 'index.php#shop';
        backLink.textContent = 'Explore the collection';
        form.append(backLink);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorMessage.hidden = true;
        if (!form.reportValidity()) return;

        const formData = new FormData(form);
        const customer = Object.fromEntries(formData.entries());
        const items = orderLines.map(({ product, variant, quantity }) => ({ id: product.id, variant_id: variant?.id ?? 0, quantity }));
        submitButton.disabled = true;
        submitButton.firstChild.textContent = 'Placing your order… ';

        try {
            const response = await fetch('api/place-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    customer,
                    items,
                    checkout_token: document.querySelector('#product-catalog').dataset.checkoutToken,
                }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.error || 'Your order could not be placed.');
            if (typeof payload.redirect !== 'string' || !/^checkout\.php\?order=\d+$/.test(payload.redirect)) {
                throw new Error('We could not confirm your order. Please try again.');
            }
            try {
                localStorage.removeItem(storageKey);
            } catch (storageError) {
                console.warn('The completed bag could not be cleared.', storageError);
            }
            window.location.assign(payload.redirect);
        } catch (error) {
            errorMessage.textContent = error instanceof Error ? error.message : 'Your order could not be placed. Please try again.';
            errorMessage.hidden = false;
            submitButton.disabled = false;
            submitButton.firstChild.textContent = 'Place cash-on-delivery order ';
        }
    });
})();
