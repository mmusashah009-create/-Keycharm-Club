<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$demoProducts = [
    ['id' => 1, 'name' => 'Sculpted Wool Coat', 'category' => 'Outerwear', 'price' => 285, 'badge' => 'BESTSELLER', 'image' => 'photo-1539109136881-3be0616acf4b', 'description' => 'A quietly dramatic silhouette in double-faced Italian wool.'],
    ['id' => 2, 'name' => 'The Everyday Knit', 'category' => 'Knitwear', 'price' => 148, 'badge' => 'NEW ARRIVAL', 'image' => 'photo-1576566588028-4147f3842f27', 'description' => 'Soft, weighty organic cotton with a perfectly relaxed fit.'],
    ['id' => 3, 'name' => 'Forma Leather Tote', 'category' => 'Accessories', 'price' => 220, 'badge' => '', 'image' => 'photo-1590874103328-eac38a683ce7', 'description' => 'A considered carryall, cut from supple responsibly sourced leather.'],
    ['id' => 4, 'name' => 'Sunday Tailored Trouser', 'category' => 'Clothing', 'price' => 175, 'badge' => 'LOW STOCK', 'image' => 'photo-1509631179647-0177331693ae', 'description' => 'Easy movement, thoughtful tailoring, and a little extra length.'],
    ['id' => 5, 'name' => 'No. 04 Ribbed Tank', 'category' => 'Essentials', 'price' => 68, 'badge' => '', 'image' => 'photo-1503342217505-b0a15ec3261c', 'description' => 'An essential layer made to be worn on repeat.'],
    ['id' => 6, 'name' => 'Soft Structure Blazer', 'category' => 'Outerwear', 'price' => 248, 'badge' => 'JUST IN', 'image' => 'photo-1591047139829-d91aecb6caea', 'description' => 'Unlined and endlessly versatile. The blazer, rethought.'],
];

$products = $demoProducts;
$catalogIsLive = false;
try {
    $pdo = getDatabaseConnection();
    $statement = $pdo->query('SELECT id, name, category, price, badge, image, description FROM products WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    $databaseProducts = $statement->fetchAll();
    if ($databaseProducts !== []) {
        $products = $databaseProducts;
        $catalogIsLive = true;
    }
} catch (PDOException $exception) {
    error_log('NOIR & FORM catalog database connection failed: ' . $exception->getMessage());
}

$categories = ['All pieces', 'Clothing', 'Outerwear', 'Knitwear', 'Accessories', 'Essentials'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111210">
    <meta name="description" content="Thoughtful essentials, made to stay. Discover considered clothing and accessories at NOIR & FORM.">
    <title>NOIR &amp; FORM — Thoughtful essentials, made to stay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#111210',
                        paper: '#f2f0eb',
                        acid: '#d5f36a',
                        muted: '#85857e'
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-ink text-paper antialiased">
    <div class="announcement-bar">
        <p>Complimentary shipping on orders of $150 or more <span aria-hidden="true">✳</span> Designed with intention. Worn on repeat.</p>
    </div>

    <header class="site-header" id="top">
        <div class="header-main">
            <button class="icon-button menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="primary-navigation">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
            </button>
            <a class="wordmark" href="#top" aria-label="NOIR and FORM home">NOIR<span>&amp;</span>FORM<span class="wordmark-period">.</span></a>
            <nav class="desktop-nav" id="primary-navigation" aria-label="Main navigation">
                <a href="#shop">Shop all</a>
                <a href="#shop" data-category-link="Clothing">Clothing</a>
                <a href="#shop" data-category-link="Accessories">Accessories</a>
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
        <?php if (!$catalogIsLive): ?>
            <div class="setup-note" role="status">Preview collection shown. Import <code>database/schema.sql</code> to connect your MySQL catalog.</div>
        <?php endif; ?>

        <section class="hero" aria-labelledby="hero-title">
            <img class="hero-image" src="https://images.unsplash.com/photo-1539109136881-3be0616acf4b?auto=format&amp;fit=crop&amp;w=2200&amp;q=88" alt="Model wearing a sculptural neutral-toned outfit in warm evening light">
            <div class="hero-shade"></div>
            <div class="hero-content">
                <p class="eyebrow"><span></span> The new seasonal edit · 01 / 04</p>
                <h1 id="hero-title">Less, but<br><em>better.</em></h1>
                <p class="hero-copy">Thoughtful pieces for wherever<br class="desktop-break"> the day takes you.</p>
                <a class="button button-light" href="#shop">Explore the collection <span aria-hidden="true">↗</span></a>
            </div>
            <div class="hero-caption"><span>THE IN-BETWEEN EDIT</span><span>01 — 04</span></div>
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
                    <p class="eyebrow">THE CONSIDERED WARDROBE</p>
                    <h2>Pieces to <em>keep.</em></h2>
                </div>
                <a class="text-link" href="#shop">Explore everything <span aria-hidden="true">↗</span></a>
            </div>
            <div class="shop-toolbar">
                <div class="category-list" role="group" aria-label="Filter products by category">
                    <?php foreach ($categories as $index => $category): ?>
                        <button class="category-chip<?= $index === 0 ? ' active' : '' ?>" type="button" data-filter="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></button>
                    <?php endforeach; ?>
                </div>
                <button class="sort-button" type="button" data-sort-toggle>Featured <span aria-hidden="true">⌄</span></button>
            </div>

            <div class="product-grid" id="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card" data-category="<?= htmlspecialchars((string) $product['category'], ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" data-price="<?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="product-image-wrap">
                            <img src="https://images.unsplash.com/<?= htmlspecialchars((string) $product['image'], ENT_QUOTES, 'UTF-8') ?>?auto=format&amp;fit=crop&amp;w=900&amp;q=82" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                            <?php if (!empty($product['badge'])): ?>
                                <span class="product-badge"><?= htmlspecialchars((string) $product['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <button class="quick-add" type="button" data-add-to-cart="<?= (int) $product['id'] ?>" aria-label="Add <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?> to bag">Add to bag <span aria-hidden="true">+</span></button>
                            <button class="favorite-button" type="button" aria-label="Add <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?> to wishlist" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.8c0 5.2-8.8 10-8.8 10s-8.8-4.8-8.8-10a4.7 4.7 0 0 1 8.8-2.2 4.7 4.7 0 0 1 8.8 2.2Z"/></svg></button>
                        </div>
                        <div class="product-info">
                            <div><p class="product-category"><?= htmlspecialchars((string) $product['category'], ENT_QUOTES, 'UTF-8') ?></p><h3><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></h3></div>
                            <p class="product-price">$<?= number_format((float) $product['price'], 0) ?></p>
                        </div>
                        <p class="product-description"><?= htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="swatches" aria-label="Available colors"><span class="swatch swatch-dark"></span><span class="swatch swatch-sand"></span><span class="swatch swatch-stone"></span></div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="empty-results" hidden>No pieces found. Try another search or category.</p>
            <div class="shop-more"><a class="button button-outline" href="#shop">View all pieces <span aria-hidden="true">↗</span></a></div>
        </section>

        <section class="editorial" id="story">
            <div class="editorial-image"><img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&amp;fit=crop&amp;w=1400&amp;q=85" alt="Two friends sharing a sunlit city afternoon in relaxed, timeless clothing" loading="lazy"></div>
            <div class="editorial-copy">
                <p class="eyebrow">A DIFFERENT KIND OF CLOSET</p>
                <h2>Wear what<br>you <em>believe in.</em></h2>
                <p>We make fewer, better things. Designed with care, made with good materials, and built to become the pieces you reach for without thinking.</p>
                <a class="text-link" href="#newsletter">Get to know us <span aria-hidden="true">↗</span></a>
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
            <a class="wordmark footer-wordmark" href="#top">NOIR<span>&amp;</span>FORM<span class="wordmark-period">.</span></a>
            <p>Thoughtful essentials,<br>made to stay.</p>
            <nav aria-label="Footer navigation"><a href="#shop">Shop</a><a href="#story">Our approach</a><a href="#newsletter">Contact</a><a href="#newsletter">Instagram ↗</a><a href="admin/login.php">Admin ↗</a></nav>
            <a class="back-top" href="#top">Back to top ↑</a>
        </div>
        <div class="footer-bottom"><span>© 2025 NOIR &amp; FORM STUDIO</span><span>MADE WITH INTENTION · WORN EVERYWHERE</span><div><a href="#newsletter">Privacy</a><a href="#newsletter">Terms</a></div></div>
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
            <div class="cart-subtotal"><span>Subtotal</span><span data-cart-subtotal>$0</span></div>
            <p>Pay in cash when your order arrives.</p>
            <a class="cart-continue" href="cart.php">View full bag <span aria-hidden="true">↗</span></a>
            <a class="cart-checkout-link" href="checkout.php" data-drawer-checkout>Continue to checkout <span aria-hidden="true">↗</span></a>
        </div>
    </aside>
    <div class="toast" role="status" aria-live="polite"></div>
    <script src="assets/js/store.js" defer></script>
</body>
</html>
