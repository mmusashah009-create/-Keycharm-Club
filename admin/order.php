<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
requireAdmin();
$pdo = getDatabaseConnection();
$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($orderId === false || $orderId === null || $orderId < 1) {
    http_response_code(400);
    exit('A valid order number is required.');
}
$orderQuery = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
$orderQuery->execute([':id' => $orderId]);
$order = $orderQuery->fetch();
if ($order === false) {
    http_response_code(404);
    exit('Order not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? null);
    $validStatuses = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (($_POST['action'] ?? '') === 'mark_paid') {
        if ($order['payment_method'] !== 'cash_on_delivery' || $order['payment_status'] !== 'pending') {
            setFlashMessage('Only an unpaid cash-on-delivery order can be marked as collected.', 'error');
        } else {
            $update = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = :id AND payment_method = 'cash_on_delivery' AND payment_status = 'pending'");
            $update->execute([':id' => $orderId]);
            setFlashMessage($update->rowCount() > 0 ? 'Cash payment marked as collected.' : 'No payment status was changed.', $update->rowCount() > 0 ? 'success' : 'error');
        }
    } else {
        $status = (string) ($_POST['order_status'] ?? '');
        if (!in_array($status, $validStatuses, true)) {
            setFlashMessage('Choose a valid fulfillment status.', 'error');
        } elseif ($order['payment_method'] !== 'cash_on_delivery' && $order['payment_status'] !== 'paid'
            && in_array($status, ['processing', 'shipped', 'delivered'], true)) {
            setFlashMessage('This order must be paid before it can be fulfilled.', 'error');
        } else {
            $update = $pdo->prepare('UPDATE orders SET order_status = :status WHERE id = :id');
            $update->execute([':status' => $status, ':id' => $orderId]);
            setFlashMessage('Order fulfillment status updated.');
        }
    }
    redirectTo('order.php?id=' . $orderId);
}

$itemsQuery = $pdo->prepare('SELECT product_name, product_image, color_name, unit_price, quantity, line_total FROM order_items WHERE order_id = :id ORDER BY id');
$itemsQuery->execute([':id' => $orderId]);
$items = $itemsQuery->fetchAll();
$validStatuses = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];
adminHeader('Order #' . $orderId, 'orders');
?>
<a class="admin-back-link" href="orders.php">← Back to all orders</a>
<div class="order-detail-layout">
    <section class="admin-panel">
        <div class="panel-heading"><div><p class="eyebrow">ORDER PLACED <?= escapeHtml(date('F j, Y · g:i a', strtotime($order['created_at']))) ?></p><h2>Order #<?= (int) $order['id'] ?></h2></div><span class="status-pill <?= escapeHtml($order['payment_status']) ?>"><?= escapeHtml(ucfirst($order['payment_status'])) ?></span></div>
        <h3 class="detail-subheading">Items ordered</h3>
        <?php foreach ($items as $item): ?>
            <article class="order-detail-item">
                <img src="<?= escapeHtml(productImageUrl((string) $item['product_image'])) ?><?= str_starts_with((string) $item['product_image'], 'photo-') ? '?auto=format&amp;fit=crop&amp;w=180&amp;q=72' : '' ?>" alt="">
                <div><h3><?= escapeHtml($item['product_name']) ?></h3><p><?= $item['color_name'] !== '' ? 'Color: ' . escapeHtml($item['color_name']) . ' · ' : '' ?>Rs <?= number_format((float) $item['unit_price'], 2) ?> × <?= (int) $item['quantity'] ?></p></div>
                <strong>Rs <?= number_format((float) $item['line_total'], 2) ?></strong>
            </article>
        <?php endforeach; ?>
        <div class="detail-totals">
            <p><span>Subtotal</span><span>Rs <?= number_format((float) $order['subtotal'], 2) ?></span></p>
            <p><span>Shipping</span><span><?= (float) $order['shipping'] === 0.0 ? 'Complimentary' : 'Rs ' . number_format((float) $order['shipping'], 2) ?></span></p>
            <?php if ((float) $order['refunded_amount'] > 0): ?><p><span>Refunded on previous payment</span><span>−Rs <?= number_format((float) $order['refunded_amount'], 2) ?></span></p><?php endif; ?>
            <p class="grand-total"><span>Total</span><strong>Rs <?= number_format((float) $order['total'], 2) ?> PKR</strong></p>
        </div>
    </section>
    <aside class="admin-panel order-customer-panel">
        <div class="panel-heading"><div><p class="eyebrow">SHIP TO</p><h2>Customer</h2></div></div>
        <address>
            <strong><?= escapeHtml($order['customer_name']) ?></strong><br>
            <?= escapeHtml($order['address_line1']) ?><br>
            <?php if ($order['address_line2'] !== ''): ?><?= escapeHtml($order['address_line2']) ?><br><?php endif; ?>
            <?= escapeHtml($order['city']) ?>, <?= escapeHtml($order['region']) ?> <?= escapeHtml($order['postal_code']) ?><br>
            <?= escapeHtml($order['country']) ?>
        </address>
        <p><a href="mailto:<?= escapeHtml($order['customer_email']) ?>"><?= escapeHtml($order['customer_email']) ?></a></p>
        <p><a href="tel:<?= escapeHtml($order['customer_phone']) ?>"><?= escapeHtml($order['customer_phone']) ?></a></p>
        <div class="status-update"><p class="eyebrow">PAYMENT · <?= escapeHtml(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?></p><p>Payment: <span class="status-pill <?= escapeHtml($order['payment_status']) ?>"><?= escapeHtml($order['payment_method'] === 'cash_on_delivery' && $order['payment_status'] === 'paid' ? 'Cash collected' : ucfirst($order['payment_status'])) ?></span></p>
            <?php if ($order['payment_method'] === 'cash_on_delivery' && $order['payment_status'] === 'pending'): ?>
                <form method="post" class="admin-form cash-payment-form">
                    <input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>">
                    <input type="hidden" name="action" value="mark_paid">
                    <button class="admin-primary-button" type="submit">Mark cash as collected</button>
                </form>
            <?php elseif ($order['payment_method'] === 'legacy_card'): ?>
                <p>This is a historical card order from before cash on delivery was enabled.</p>
            <?php endif; ?>
            <p class="eyebrow">FULFILLMENT</p><p>Current status: <span class="status-pill <?= escapeHtml($order['order_status']) ?>"><?= escapeHtml(ucfirst($order['order_status'])) ?></span></p>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>">
                <label>Update order status<select name="order_status"><?php foreach ($validStatuses as $status): ?><option value="<?= escapeHtml($status) ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?> <?= $order['payment_method'] !== 'cash_on_delivery' && $order['payment_status'] !== 'paid' && in_array($status, ['processing', 'shipped', 'delivered'], true) ? 'disabled' : '' ?>><?= escapeHtml(ucfirst($status)) ?></option><?php endforeach; ?></select></label>
                <button class="admin-primary-button" type="submit">Save status</button>
            </form>
        </div>
    </aside>
</div>
<?php adminFooter(); ?>
