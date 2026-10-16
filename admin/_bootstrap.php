<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

function adminHeader(string $title, string $activePage): void
{
    header('Cache-Control: no-store, private');
    $flash = consumeFlashMessage();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#fff8ed">
        <title><?= escapeHtml($title) ?> — Pop Shop Admin</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Pacifico&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
        <link rel="stylesheet" href="../assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin.css') ?>">
    </head>
    <body class="bg-ink text-paper admin-body">
    <header class="site-header admin-header">
        <div class="header-main">
            <a class="wordmark" href="index.php">Pop Shop <span class="admin-tag">ADMIN</span></a>
            <nav class="admin-nav" aria-label="Admin navigation">
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php">Dashboard</a>
                <a class="<?= $activePage === 'products' ? 'active' : '' ?>" href="products.php">Products</a>
                <a class="<?= $activePage === 'orders' ? 'active' : '' ?>" href="orders.php">Orders</a>
            </nav>
            <form class="admin-logout" action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>">
                <span><?= escapeHtml($_SESSION['admin_username'] ?? '') ?></span>
                <button type="submit">Sign out</button>
            </form>
        </div>
    </header>
    <main class="admin-main">
        <div class="admin-page-heading"><div><p class="eyebrow">POP SHOP · STUDIO</p><h1><?= escapeHtml($title) ?></h1></div><a class="admin-store-link" href="../index.php">View store ↗</a></div>
        <?php if ($flash !== null): ?>
            <div class="admin-flash <?= escapeHtml($flash['type']) ?>" role="status"><?= escapeHtml($flash['message']) ?></div>
        <?php endif; ?>
    <?php
}

function adminFooter(): void
{
    ?>
    <script src="../assets/js/motion.js" defer></script>
    </main>
    <footer class="admin-footer"><span>POP SHOP · ADMINISTRATION</span><a href="../index.php">Return to storefront ↗</a></footer>
    </body>
    </html>
    <?php
}
