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
        cart = JSON.parse(localStorage.getItem(storageKey) || '{}');
        if (!cart || typeof cart !== 'object' || Array.isArray(cart)) cart = {};
    } catch (error) {
        console.warn('The saved shopping bag could not be read.', error);
    }

    const money = (cents) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100);
    const orderLines = [];
    let subtotal = 0;

    Object.entries(cart).forEach(([id, rawQuantity]) => {
        const product = products.get(id);
        const quantity = Number(rawQuantity);
        if (!product || !Number.isInteger(quantity) || quantity < 1 || quantity > 10) {
            delete cart[id];
            return;
        }
        const unitPriceCents = Math.round(Number(product.price) * 100);
        subtotal += unitPriceCents * quantity;
        orderLines.push({ product, quantity, unitPriceCents });
    });

    const count = orderLines.reduce((sum, line) => sum + line.quantity, 0);
    const shippingCents = count === 0 || subtotal >= 15000 ? 0 : 1200;
    const totalCents = subtotal + shippingCents;
    document.querySelector('[data-checkout-count]').textContent = count ? `(${count})` : '';
    document.querySelector('[data-checkout-subtotal]').textContent = money(subtotal);
    document.querySelector('[data-checkout-shipping]').textContent = count === 0 ? '—' : (shippingCents === 0 ? 'Complimentary' : money(shippingCents));
    document.querySelector('[data-checkout-total]').textContent = money(totalCents);

    orderLines.forEach(({ product, quantity, unitPriceCents }) => {
        const row = document.createElement('div');
        row.className = 'checkout-item';
        const image = document.createElement('img');
        image.src = `https://images.unsplash.com/${encodeURIComponent(product.image)}?auto=format&fit=crop&w=180&q=76`;
        image.alt = '';
        const details = document.createElement('div');
        const name = document.createElement('p');
        name.textContent = product.name;
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
        const items = orderLines.map(({ product, quantity }) => ({ id: product.id, quantity }));
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
