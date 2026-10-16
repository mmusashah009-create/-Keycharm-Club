<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
requireAdmin();
$pdo = getDatabaseConnection();
$stats = [
    'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending' AND payment_method = 'cash_on_delivery'")->fetchColumn(),
    'products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND category = 'Keychains'")->fetchColumn(),
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total - refunded_amount), 0) FROM orders WHERE payment_status IN ('paid', 'partially_refunded', 'refunded')")->fetchColumn(),
];
$recentOrders = $pdo->query(
    'SELECT id, customer_name, customer_email, total, payment_status, order_status, created_at
     FROM orders ORDER BY created_at DESC, id DESC LIMIT 8'
)->fetchAll();

adminHeader('Dashboard', 'dashboard');
?>
<section class="admin-stats" aria-label="Store overview">
    <article class="stat-card"><p>ALL ORDERS</p><strong><?= $stats['orders'] ?></strong><span>Orders received</span></article>
    <article class="stat-card"><p>COD TO COLLECT</p><strong><?= $stats['pending'] ?></strong><span>Orders awaiting cash on delivery</span></article>
    <article class="stat-card"><p>ACTIVE PRODUCTS</p><strong><?= $stats['products'] ?></strong><span>Available in the store</span></article>
    <article class="stat-card"><p>COLLECTED REVENUE</p><strong>Rs <?= number_format($stats['revenue'], 2) ?></strong><span>Payments marked as received</span></article>
</section>
<section class="admin-panel">
    <div class="panel-heading"><div><p class="eyebrow">THE LATEST</p><h2>Recent orders</h2></div><a class="admin-text-link" href="orders.php">All orders ↗</a></div>
    <?php if ($recentOrders === []): ?>
        <p class="admin-empty">No orders yet. Once shoppers check out, their orders will appear here.</p>
    <?php else: ?>
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>ORDER</th><th>CUSTOMER</th><th>DATE</th><th>TOTAL</th><th>PAYMENT</th><th>FULFILLMENT</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td>#<?= (int) $order['id'] ?></td>
                    <td><strong><?= escapeHtml($order['customer_name']) ?></strong><span><?= escapeHtml($order['customer_email']) ?></span></td>
                    <td><?= escapeHtml(date('M j, Y', strtotime($order['created_at']))) ?></td>
                    <td>Rs <?= number_format((float) $order['total'], 2) ?></td>
                    <td><span class="status-pill <?= escapeHtml($order['payment_status']) ?>"><?= escapeHtml(ucfirst($order['payment_status'])) ?></span></td>
                    <td><span class="status-pill <?= escapeHtml($order['order_status']) ?>"><?= escapeHtml(ucfirst($order['order_status'])) ?></span></td>
                    <td><a class="table-action" href="order.php?id=<?= (int) $order['id'] ?>">Details ↗</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php adminFooter(); ?>
