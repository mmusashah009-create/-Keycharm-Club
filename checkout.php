<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
$storefrontOrder = $_SESSION['noir_form_order_complete'] ?? null;
if (isset($_GET['order'], $_SESSION['noir_form_order_complete'])
    && filter_var($_GET['order'], FILTER_VALIDATE_INT) !== false
    && (int) $_GET['order'] === (int) $_SESSION['noir_form_order_complete']) {
    $pdo = getDatabaseConnection();
    $statement = $pdo->prepare('SELECT id, customer_name, total, payment_status, payment_method FROM orders WHERE id = :id');
    $statement->execute([':id' => (int) $_SESSION['noir_form_order_complete']]);
    $order = $statement->fetch() ?: null;
    unset($_SESSION['checkout_submission_token']);
} else {
    $order = null;
}
unset($_SESSION['noir_form_order_complete']);
$orderNumber = $order !== null ? 'NF-' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT) : null;

$products = [];
if ($order === null) {
    $pdo = getDatabaseConnection();
    $products = $pdo->query("SELECT id, name, category, price, image FROM products WHERE is_active = 1 AND category = 'Keychains' ORDER BY sort_order ASC, id ASC")->fetchAll();
    $variantRows = $pdo->query(
        "SELECT v.id, v.product_id, v.color_name, v.color_hex, v.image
         FROM product_variants v
         JOIN products p ON p.id = v.product_id
         WHERE p.is_active = 1 AND p.category = 'Keychains'
         ORDER BY v.sort_order, v.id"
    )->fetchAll();
    $variantsByProduct = [];
    foreach ($variantRows as $variant) {
        $variant['id'] = (int) $variant['id'];
        $productId = (int) $variant['product_id'];
        unset($variant['product_id']);
        $variantsByProduct[$productId][] = $variant;
    }
    foreach ($products as &$product) {
        $product['variants'] = $variantsByProduct[(int) $product['id']] ?? [];
    }
    unset($product);
    if (!isset($_SESSION['checkout_submission_token'])) {
        $_SESSION['checkout_submission_token'] = bin2hex(random_bytes(32));
    }
}
$catalogJson = json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= escapeHtml(csrfToken()) ?>">
    <meta name="theme-color" content="#fff8ed">
    <title><?= $order !== null ? 'Order received' : 'Checkout' ?> — Pop Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Pacifico&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/pages.css?v=<?= filemtime(__DIR__ . '/assets/css/pages.css') ?>">
</head>
<body class="bg-ink text-paper">
    <header class="site-header">
        <div class="header-main page-header">
            <a class="wordmark" href="index.php">Pop Shop</a>
            <span class="secure-label">CASH ON DELIVERY</span>
            <a class="page-back-bag" href="cart.php">← Your bag</a>
        </div>
    </header>
    <main class="store-page checkout-page">
        <?php if ($order !== null): ?>
            <section class="confirmation-card" aria-live="polite">
                <p class="eyebrow">ORDER CONFIRMED</p>
                <h1>Thank you.<br><em>Good choice.</em></h1>
                <div class="confirmation-order-number" aria-label="Your order number">
                    <span>Your order number</span>
                    <strong><?= escapeHtml($orderNumber) ?></strong>
                </div>
                <p>Hi, <?= escapeHtml($order['customer_name']) ?>. Your order is confirmed. Pay Rs <?= number_format((float) $order['total'], 2) ?> in cash when it arrives.</p>
                <p class="tax-note">Keep this number for your records and have the order total ready when it is delivered.</p>
                <a class="checkout-link" href="index.php">Back to the collection <span aria-hidden="true">↗</span></a>
            </section>
        <?php else: ?>
            <a class="back-link" href="cart.php">← Return to your bag</a>
            <?php if ($storefrontOrder !== null): ?>
                <p class="notice-banner">Your order was already received. Check your order confirmation or return to your bag.</p>
            <?php endif; ?>
            <div class="page-title-row"><div><p class="eyebrow">ONE LAST DETAIL</p><h1>Almost <em>yours.</em></h1></div><span class="secure-label">CASH ON DELIVERY</span></div>
            <div class="checkout-layout">
                <form class="checkout-form" id="checkout-form" novalidate>
                    <h2>Shipping details</h2>
                    <p class="form-intro">Add your contact and delivery details. Pay in cash when your order arrives.</p>
                    <div class="form-grid">
                        <label class="form-field form-wide">Full name<input name="name" type="text" maxlength="160" autocomplete="name" required></label>
                        <label class="form-field">Email address<input name="email" type="email" maxlength="254" autocomplete="email" required></label>
                        <label class="form-field">Phone number<input name="phone" type="tel" maxlength="40" autocomplete="tel" required></label>
                        <label class="form-field form-wide">Street address<input name="address_line1" type="text" maxlength="200" autocomplete="address-line1" required></label>
                        <label class="form-field form-wide">Apartment, suite, etc. <span>(optional)</span><input name="address_line2" type="text" maxlength="200" autocomplete="address-line2"></label>
                        <label class="form-field">City<input name="city" type="text" maxlength="100" autocomplete="address-level2" required></label>
                        <label class="form-field">State / region<input name="region" type="text" maxlength="100" autocomplete="address-level1" required></label>
                        <label class="form-field">ZIP / postal code<input name="postal_code" type="text" maxlength="30" autocomplete="postal-code" required></label>
                        <label class="form-field">Country<input name="country" type="text" maxlength="100" autocomplete="country-name" required></label>
                    </div>
                    <div class="payment-explainer"><span aria-hidden="true">✳</span><p>Cash on delivery. No card details or online payment are required. Please have the order total ready when your delivery arrives.</p></div>
                    <p class="checkout-error" role="alert" data-checkout-error hidden></p>
                    <button class="checkout-link checkout-submit" type="submit">Place cash-on-delivery order <span aria-hidden="true">↗</span></button>
                </form>
                <aside class="order-summary" aria-labelledby="checkout-summary-title">
                    <h2 id="checkout-summary-title">Your order <span data-checkout-count></span></h2>
                    <div class="checkout-items" data-checkout-items></div>
                    <div class="summary-row"><span>Subtotal</span><span data-checkout-subtotal>Rs 0.00</span></div>
                    <div class="summary-row"><span>Shipping</span><span data-checkout-shipping>—</span></div>
                    <div class="summary-row summary-total"><span>Total</span><span data-checkout-total>Rs 0.00</span></div>
                    <p class="tax-note">Prices are in Pakistani rupees (PKR). Pay in cash when your order is delivered.</p>
                </aside>
            </div>
        <?php endif; ?>
    </main>
    <?php if ($order === null): ?>
        <script type="application/json" id="product-catalog"><?= $catalogJson ?></script>
        <script>document.querySelector('#product-catalog').dataset.checkoutToken = <?= json_encode($_SESSION['checkout_submission_token'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;</script>
        <script src="assets/js/checkout.js?v=<?= filemtime(__DIR__ . '/assets/js/checkout.js') ?>" defer></script>
    <?php else: ?>
        <script>try { localStorage.removeItem('noir-form-cart'); } catch (error) { console.warn('The completed bag could not be cleared.', error); }</script>
    <?php endif; ?>
    <script src="assets/js/motion.js" defer></script>
</body>
</html>
