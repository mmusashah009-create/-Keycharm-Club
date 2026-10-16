<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
requireAdmin();
$pdo = getDatabaseConnection();
$paymentStatuses = ['all', 'pending', 'paid', 'partially_refunded', 'failed', 'refunded'];
$paymentFilter = in_array($_GET['payment'] ?? 'all', $paymentStatuses, true) ? (string) ($_GET['payment'] ?? 'all') : 'all';
$search = trim((string) ($_GET['q'] ?? ''));
$where = [];
$parameters = [];
if ($paymentFilter !== 'all') {
    $where[] = 'payment_status = :payment_status';
    $parameters[':payment_status'] = $paymentFilter;
}
if ($search !== '') {
    $where[] = '(CAST(id AS CHAR) = :order_id OR customer_email LIKE :search_email OR customer_name LIKE :search_name)';
    $parameters[':order_id'] = ltrim($search, '#');
    $parameters[':search_email'] = '%' . $search . '%';
    $parameters[':search_name'] = '%' . $search . '%';
}
$sql = 'SELECT id, customer_name, customer_email, total, payment_status, order_status, created_at FROM orders';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC, id DESC LIMIT 200';
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$orders = $statement->fetchAll();
adminHeader('Orders', 'orders');
?>
<section class="admin-panel">
    <div class="panel-heading"><div><p class="eyebrow">EVERY ORDER, IN ONE PLACE</p><h2><?= count($orders) ?> orders<?= count($orders) === 200 ? '+' : '' ?></h2></div></div>
    <form class="order-filters" method="get">
        <label>Search by name, email, or order number<input name="q" value="<?= escapeHtml($search) ?>" placeholder="e.g. #1024 or name@email.com"></label>
        <label>Payment status<select name="payment"><?php foreach ($paymentStatuses as $status): ?><option value="<?= escapeHtml($status) ?>" <?= $paymentFilter === $status ? 'selected' : '' ?>><?= escapeHtml(ucfirst($status)) ?></option><?php endforeach; ?></select></label>
        <button class="admin-secondary-button" type="submit">Filter orders</button>
    </form>
    <?php if ($orders === []): ?>
        <p class="admin-empty">No orders match these filters.</p>
    <?php else: ?>
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>ORDER</th><th>CUSTOMER</th><th>DATE</th><th>TOTAL</th><th>PAYMENT</th><th>FULFILLMENT</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
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
