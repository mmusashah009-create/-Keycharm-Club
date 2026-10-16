<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/product-images.php';

$products = [];
$catalogConnected = false;
try {
    $pdo = getDatabaseConnection();
    $statement = $pdo->query("SELECT id, name, category, price, badge, image, description FROM products WHERE is_active = 1 AND category = 'Keychains' ORDER BY sort_order ASC, id ASC");
    $products = $statement->fetchAll();
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
        $variantByProductId = (int) $variant['product_id'];
        unset($variant['product_id']);
        $variantsByProduct[$variantByProductId][] = $variant;
    }
    foreach ($products as &$product) {
        $product['variants'] = $variantsByProduct[(int) $product['id']] ?? [];
    }
    unset($product);
    $catalogConnected = true;
} catch (PDOException $exception) {
    error_log('Pop Shop catalog database connection failed: ' . $exception->getMessage());
}

$categories = ['Keychains'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fff8ed">
    <meta name="description" content="Meet the little things that make a big difference. Discover playful keychains, bag charms, and accessories at Pop Shop.">
    <title>Pop Shop — Keychains, charms &amp; little accessories</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#fff8ed',
                        paper: '#281a39',
                        acid: '#d5ff3f',
                        muted: '#766482'
                    },
                    fontFamily: {
                        sans: ['Inter', 'Arial', 'sans-serif'],
                        display: ['Georgia', 'serif']
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Pacifico&family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body class="bg-ink text-paper antialiased">
    <div class="announcement-bar">
        <p>Free shipping on orders of Rs 150+ <span aria-hidden="true">✳</span> Tiny charms. Big main-character energy.</p>
    </div>

    <header class="site-header" id="top">
        <div class="header-main">
            <button class="icon-button menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="primary-navigation">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
            </button>
            <a class="wordmark" href="#top" aria-label="Pop Shop home">Pop Shop</a>
            <nav class="desktop-nav" id="primary-navigation" aria-label="Main navigation">
                <a href="#shop">Shop</a>
                <a href="#shop" data-category-link="Keychains">Keychains</a>
                <a href="#story">Our approach</a>
            </nav>
            <div class="header-actions">
                <button class="icon-button search-toggle" type="button" aria-label="Search products">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
                </button>
                <a class="account-link" href="#newsletter">Sign in</a>
                <button class="bag-button" type="button" aria-label="Shopping bag, 0 items" data-open-cart>
                    Bag <span class="bag-count" data-cart-count>0</span>
                </button>
            </div>
        </div>
        <form class="search-panel" role="search" aria-label="Search the collection">
            <label class="sr-only" for="site-search">Search products</label>
            <input id="site-search" type="search" placeholder="Search the collection..." autocomplete="off">
            <button type="button" class="search-close" aria-label="Close search">×</button>
        </form>
    </header>

    <main>
        <?php if (!$catalogConnected): ?>
            <div class="setup-note" role="status">The product catalog is unavailable. Check the MySQL connection, then add keychains from the admin panel.</div>
        <?php endif; ?>

        <section class="hero" aria-labelledby="hero-title">
            <img class="hero-image" src="assets/images/pop-shop-hanging-charms.svg?v=<?= filemtime(__DIR__ . '/assets/images/pop-shop-hanging-charms.svg') ?>" alt="Colorful keychains, mini plush charms, and bag accessories hanging from a display rail">
            <div class="hero-shade"></div>
            <div class="floating-accessories" aria-hidden="true">
                <span class="floating-accessory accessory-keychains">Keychains</span>
                <span class="floating-accessory accessory-bag-charms">Bag charms</span>
                <span class="floating-accessory accessory-mini-plush">Mini plush</span>
                <span class="floating-accessory accessory-phone-charms">Phone charms</span>
                <span class="floating-accessory accessory-lanyards">Lanyards</span>
                <span class="floating-accessory accessory-hair-clips">Hair clips</span>
            </div>
            <div class="hero-content">
                <p class="eyebrow"><span></span> Tiny treasures · Big personality</p>
                <h1 id="hero-title">Clip on<br><em>the fun.</em></h1>
                <p class="hero-copy">Keychains, bag charms, and tiny<br class="desktop-break"> treasures with big personality.</p>
                <a class="button button-light" href="#shop">Find your charm <span aria-hidden="true">↗</span></a>
            </div>
            <div class="hero-caption"><span>THE POP SHOP CHARM BAR</span><span>HANG A LITTLE JOY</span></div>
            <div class="hero-scroll" aria-hidden="true"><span></span> SCROLL TO DISCOVER</div>
        </section>

        <section class="trust-strip" aria-label="Our commitments">
            <p><span>01</span> Made with intention</p>
            <i aria-hidden="true"></i>
            <p><span>02</span> Better materials, always</p>
            <i aria-hidden="true"></i>
            <p><span>03</span> Made to last, not landfill</p>
        </section>

        <section class="shop-section" id="shop">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">YOUR LITTLE THING</p>
                    <h2>Keychains for <em>every day.</em></h2>
                </div>
                <a class="text-link" href="#shop">Explore everything <span aria-hidden="true">↗</span></a>
            </div>
            <div class="shop-toolbar">
                <div class="category-list" role="group" aria-label="Filter products by category">
                    <?php foreach ($categories as $index => $category): ?>
                        <button class="category-chip<?= $index === 0 ? ' active' : '' ?>" type="button" data-filter="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></button>
                    <?php endforeach; ?>
                </div>
                <?php if ($products !== []): ?>
                    <button class="sort-button" type="button" data-sort-toggle>Featured <span aria-hidden="true">⌄</span></button>
                <?php endif; ?>
            </div>

            <div class="product-grid" id="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card" data-category="<?= htmlspecialchars((string) $product['category'], ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" data-price="<?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?>" data-default-image="<?= htmlspecialchars(productImageUrl((string) $product['image']) . (str_starts_with((string) $product['image'], 'photo-') ? '?auto=format&fit=crop&w=900&q=82' : ''), ENT_QUOTES, 'UTF-8') ?>" data-variants="<?= htmlspecialchars(json_encode($product['variants'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="product-image-wrap">
                            <img src="<?= htmlspecialchars(productImageUrl((string) $product['image']), ENT_QUOTES, 'UTF-8') ?><?= str_starts_with((string) $product['image'], 'photo-') ? '?auto=format&amp;fit=crop&amp;w=900&amp;q=82' : '' ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" data-product-image loading="lazy">
                            <?php if (!empty($product['badge'])): ?>
                                <span class="product-badge"><?= htmlspecialchars((string) $product['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <button class="quick-add" type="button" data-add-to-cart="<?= (int) $product['id'] ?>" aria-label="Add <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?> to bag">Add to bag <span aria-hidden="true">+</span></button>
                            <button class="favorite-button" type="button" aria-label="Add <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?> to wishlist" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.8c0 5.2-8.8 10-8.8 10s-8.8-4.8-8.8-10a4.7 4.7 0 0 1 8.8-2.2 4.7 4.7 0 0 1 8.8 2.2Z"/></svg></button>
                        </div>
                        <div class="product-info">
                            <div><p class="product-category"><?= htmlspecialchars((string) $product['category'], ENT_QUOTES, 'UTF-8') ?></p><h3><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></h3></div>
                            <p class="product-price">Rs <?= number_format((float) $product['price'], 0) ?></p>
                        </div>
                        <p class="product-description"><?= htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($product['variants'] !== []): ?>
                            <div class="product-color-picker">
                                <p>Color: <span data-selected-color><?= htmlspecialchars((string) $product['variants'][0]['color_name'], ENT_QUOTES, 'UTF-8') ?></span></p>
                                <div class="swatches" role="group" aria-label="Choose a color for <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php foreach ($product['variants'] as $variantIndex => $variant): ?>
                                        <button class="swatch" type="button" data-color-variant="<?= (int) $variant['id'] ?>" data-color-name="<?= htmlspecialchars((string) $variant['color_name'], ENT_QUOTES, 'UTF-8') ?>" data-color-image="<?= htmlspecialchars(productImageUrl((string) $variant['image']), ENT_QUOTES, 'UTF-8') ?>" style="--variant-color: <?= productColorHex((string) $variant['color_hex']) ?>" aria-label="<?= htmlspecialchars((string) $variant['color_name'], ENT_QUOTES, 'UTF-8') ?> color" aria-pressed="<?= $variantIndex === 0 ? 'true' : 'false' ?>" title="<?= htmlspecialchars((string) $variant['color_name'], ENT_QUOTES, 'UTF-8') ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="empty-results" hidden>No keychains yet. Add your first one from the <a href="admin/login.php">admin panel</a>.</p>
            <div class="shop-more"><a class="button button-outline" href="<?= $products === [] ? 'admin/login.php' : '#shop' ?>"><?= $products === [] ? 'Add your first keychain' : 'View all keychains' ?> <span aria-hidden="true">↗</span></a></div>
        </section>

        <section class="editorial" id="story">
            <div class="editorial-image"><img src="assets/images/pop-shop-hanging-charms.svg?v=<?= filemtime(__DIR__ . '/assets/images/pop-shop-hanging-charms.svg') ?>" alt="Colorful keychains and bag charms ready to clip on and go" loading="lazy"></div>
            <div class="editorial-copy">
                <p class="eyebrow">A LITTLE JOY, WHEREVER YOU GO</p>
                <h2>Made to<br><em>go with you.</em></h2>
                <p>Find a tiny sidekick for your keys, bag, or backpack. Pick a favorite, clip it on, and take a little extra joy wherever the day goes.</p>
                <a class="text-link" href="#shop">Find your keychain <span aria-hidden="true">↗</span></a>
                <span class="editorial-index">STORY — 01</span>
            </div>
        </section>

        <section class="quote-band">
            <p class="eyebrow">A NOTE TO SELF</p>
            <blockquote>“Buy less.<br>Choose well.<br><em>Make it last.”</em></blockquote>
            <span>— VIVIENNE WESTWOOD</span>
            <div class="quote-star" aria-hidden="true">✳</div>
        </section>

        <section class="newsletter" id="newsletter">
            <div><p class="eyebrow">A LITTLE LESS NOISE. A LITTLE MORE FORM.</p><h2>Good things,<br><em>occasionally.</em></h2><p>First looks, thoughtful notes, and 10% off your first order.</p></div>
            <form class="newsletter-form">
                <label class="sr-only" for="newsletter-email">Your email address</label>
                <input id="newsletter-email" type="email" placeholder="Your email address" required>
                <button type="submit" aria-label="Subscribe to the newsletter">Join us <span aria-hidden="true">↗</span></button>
                <p class="newsletter-message" aria-live="polite">By subscribing, you agree to our privacy policy. Unsubscribe anytime.</p>
            </form>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-top">
            <a class="wordmark footer-wordmark" href="#top">Pop Shop</a>
            <p>Thoughtful essentials,<br>made to stay.</p>
            <nav aria-label="Footer navigation"><a href="#shop">Shop</a><a href="#story">Our approach</a><a href="#newsletter">Contact</a><a href="#newsletter">Instagram ↗</a><a href="admin/login.php">Admin ↗</a></nav>
            <a class="back-top" href="#top">Back to top ↑</a>
        </div>
        <div class="footer-bottom"><span>© 2026 POP SHOP</span><span>MADE WITH INTENTION · WORN EVERYWHERE</span><div><a href="#newsletter">Privacy</a><a href="#newsletter">Terms</a></div></div>
    </footer>

    <div class="cart-backdrop" data-close-cart hidden></div>
    <aside class="cart-drawer" role="dialog" aria-modal="true" aria-labelledby="cart-title" aria-hidden="true" inert>
        <div class="cart-header">
            <div><p class="eyebrow">THE GOOD STUFF</p><h2 id="cart-title">Your bag <span data-cart-heading-count>(0)</span></h2></div>
            <button class="cart-close" type="button" aria-label="Close shopping bag" data-close-cart>×</button>
        </div>
        <div class="cart-items" data-cart-items></div>
        <div class="cart-empty" data-cart-empty>Your bag is waiting for something good.</div>
        <div class="cart-footer">
            <div class="cart-subtotal"><span>Subtotal</span><span data-cart-subtotal>Rs 0</span></div>
            <p>Pay in cash when your order arrives.</p>
            <a class="cart-continue" href="cart.php">View full bag <span aria-hidden="true">↗</span></a>
            <a class="cart-checkout-link" href="checkout.php" data-drawer-checkout>Continue to checkout <span aria-hidden="true">↗</span></a>
        </div>
    </aside>
    <div class="toast" role="status" aria-live="polite"></div>
    <script src="assets/js/motion.js" defer></script>
    <script src="assets/js/store.js?v=<?= filemtime(__DIR__ . '/assets/js/store.js') ?>" defer></script>
</body>
</html>
