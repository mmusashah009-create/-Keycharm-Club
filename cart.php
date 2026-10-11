<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

$pdo = getDatabaseConnection();
$products = $pdo->query('SELECT id, name, category, price, image FROM products WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$catalogJson = json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= escapeHtml(csrfToken()) ?>">
    <meta name="theme-color" content="#111210">
    <title>Your bag — NOIR &amp; FORM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/pages.css">
</head>
<body class="bg-ink text-paper">
    <header class="site-header">
        <div class="header-main page-header">
            <a class="wordmark" href="index.php">NOIR<span>&amp;</span>FORM<span class="wordmark-period">.</span></a>
            <nav class="page-nav"><a href="index.php#shop">Continue shopping</a><a href="admin/login.php">Admin</a></nav>
            <span class="secure-label">YOUR BAG · <span data-bag-header-count>0 ITEMS</span></span>
        </div>
    </header>
    <main class="store-page">
        <a class="back-link" href="index.php">← Back to the collection</a>
        <div class="page-title-row"><div><p class="eyebrow">A FEW GOOD THINGS</p><h1>Your <em>bag.</em></h1></div><p class="bag-item-summary" data-bag-summary>0 pieces</p></div>
        <div class="bag-layout">
            <section class="bag-list" aria-label="Items in your bag">
                <p class="empty-bag-message" data-empty-bag hidden>Your bag is looking a little light.<br><a href="index.php#shop">Find something you’ll keep ↗</a></p>
                <div class="bag-items" data-bag-items></div>
            </section>
            <aside class="order-summary" aria-labelledby="summary-title">
                <h2 id="summary-title">Order summary</h2>
                <div class="summary-row"><span>Subtotal</span><span data-bag-subtotal>$0.00</span></div>
                <div class="summary-row"><span>Shipping</span><span data-bag-shipping>—</span></div>
                <p class="shipping-note">Complimentary shipping on orders of $150 or more.</p>
                <div class="summary-row summary-total"><span>Estimated total</span><span data-bag-total>$0.00</span></div>
                <p class="tax-note">No prepayment required. Pay in cash when your order arrives.</p>
                <a class="checkout-link" href="checkout.php" data-checkout-link>Continue to checkout <span aria-hidden="true">↗</span></a>
                <div class="payment-note">Cash on delivery · Pay when your order arrives.</div>
            </aside>
        </div>
    </main>
    <script type="application/json" id="product-catalog"><?= $catalogJson ?></script>
    <script src="assets/js/cart-page.js" defer></script>
</body>
</html>
