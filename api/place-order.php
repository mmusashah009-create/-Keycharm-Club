<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function orderJsonError(string $message, int $status): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_THROW_ON_ERROR);
    exit;
}

function orderJsonSuccess(int $orderId): void
{
    $_SESSION['noir_form_order_complete'] = $orderId;
    echo json_encode(['redirect' => 'checkout.php?order=' . $orderId], JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    orderJsonError('Submit the checkout form to place your order.', 405);
}

requireValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
$checkoutToken = $_SESSION['checkout_submission_token'] ?? '';
if (!is_string($checkoutToken) || strlen($checkoutToken) !== 64) {
    orderJsonError('Your checkout expired. Refresh the page and try again.', 419);
}

$request = json_decode(file_get_contents('php://input'), true);
if (!is_array($request) || !isset($request['items'], $request['customer']) || !is_array($request['items']) || !is_array($request['customer'])) {
    orderJsonError('Your checkout details could not be read. Please refresh the page and try again.', 400);
}
if (!isset($request['checkout_token']) || !is_string($request['checkout_token']) || !hash_equals($checkoutToken, $request['checkout_token'])) {
    orderJsonError('Your checkout expired. Refresh the page and try again.', 419);
}

$customer = $request['customer'];
$limits = ['name' => 160, 'email' => 254, 'phone' => 40, 'address_line1' => 200, 'address_line2' => 200, 'city' => 100, 'region' => 100, 'postal_code' => 30, 'country' => 100];
$cleanCustomer = [];
foreach ($limits as $field => $limit) {
    $value = $customer[$field] ?? null;
    if (!is_string($value)) {
        orderJsonError('Please complete every required shipping field.', 422);
    }
    $cleanCustomer[$field] = trim($value);
    if (($field !== 'address_line2' && $cleanCustomer[$field] === '') || strlen($cleanCustomer[$field]) > $limit) {
        orderJsonError('Please check the shipping details and try again.', 422);
    }
}

if (!filter_var($cleanCustomer['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9+().\-\s]{6,40}$/', $cleanCustomer['phone'])) {
    orderJsonError('Enter a valid email address and phone number.', 422);
}
if (count($request['items']) === 0 || count($request['items']) > 20) {
    orderJsonError('Your bag must have between 1 and 20 different products.', 422);
}

$quantities = [];
foreach ($request['items'] as $item) {
    if (!is_array($item) || !isset($item['id'], $item['quantity'])
        || filter_var($item['id'], FILTER_VALIDATE_INT) === false
        || filter_var($item['quantity'], FILTER_VALIDATE_INT) === false
        || filter_var($item['variant_id'] ?? 0, FILTER_VALIDATE_INT) === false) {
        orderJsonError('Your bag contains an invalid item. Please review it and try again.', 422);
    }
    $id = (int) $item['id'];
    $quantity = (int) $item['quantity'];
    $variantId = (int) ($item['variant_id'] ?? 0);
    $itemKey = $id . ':' . $variantId;
    if ($id < 1 || $variantId < 0 || $quantity < 1 || $quantity > 10 || isset($quantities[$itemKey])) {
        orderJsonError('Each product can be ordered in quantities of 1 to 10.', 422);
    }
    $quantities[$itemKey] = ['product_id' => $id, 'variant_id' => $variantId, 'quantity' => $quantity];
}

try {
    $pdo = getDatabaseConnection();

    $existingOrder = $pdo->prepare('SELECT id FROM orders WHERE checkout_token = :checkout_token');
    $existingOrder->execute([':checkout_token' => $checkoutToken]);
    $existingOrderId = $existingOrder->fetchColumn();
    if ($existingOrderId !== false) {
        orderJsonSuccess((int) $existingOrderId);
    }

    $placeholders = [];
    $parameters = [];
    foreach (array_unique(array_column($quantities, 'product_id')) as $index => $id) {
        $placeholder = ':product' . $index;
        $placeholders[] = $placeholder;
        $parameters[$placeholder] = $id;
    }

    $pdo->beginTransaction();
    $productQuery = $pdo->prepare('SELECT id, name, price, image FROM products WHERE is_active = 1 AND id IN (' . implode(', ', $placeholders) . ') FOR UPDATE');
    $productQuery->execute($parameters);
    $products = $productQuery->fetchAll();
    $productsById = [];
    foreach ($products as $product) {
        $productsById[(int) $product['id']] = $product;
    }
    if (count($productsById) !== count($parameters)) {
        $pdo->rollBack();
        orderJsonError('One or more products are no longer available. Please update your bag.', 409);
    }

    $variantQuery = $pdo->prepare('SELECT id, product_id, color_name, image FROM product_variants WHERE product_id IN (' . implode(', ', $placeholders) . ') FOR UPDATE');
    $variantQuery->execute($parameters);
    $variantsById = [];
    foreach ($variantQuery->fetchAll() as $variant) {
        $variantsById[(int) $variant['id']] = $variant;
    }

    $subtotalCents = 0;
    $lineItems = [];
    foreach ($quantities as $requestedItem) {
        $product = $productsById[$requestedItem['product_id']];
        $quantity = $requestedItem['quantity'];
        $variantId = $requestedItem['variant_id'];
        $variant = $variantId > 0 ? ($variantsById[$variantId] ?? null) : null;
        if ($variantId > 0 && (!is_array($variant) || (int) $variant['product_id'] !== $requestedItem['product_id'])) {
            $pdo->rollBack();
            orderJsonError('A selected color is no longer available. Please update your bag.', 409);
        }
        $priceCents = (int) round((float) $product['price'] * 100);
        $subtotalCents += $priceCents * $quantity;
        $lineItems[] = [
            'id' => (int) $product['id'],
            'name' => (string) $product['name'],
            'image' => is_array($variant) ? (string) $variant['image'] : (string) $product['image'],
            'color_name' => is_array($variant) ? (string) $variant['color_name'] : '',
            'price_cents' => $priceCents,
            'quantity' => $quantity,
        ];
    }

    $shippingCents = $subtotalCents >= 15000 ? 0 : 1200;
    if ($subtotalCents + $shippingCents < 50 || $subtotalCents + $shippingCents > 100000000) {
        $pdo->rollBack();
        orderJsonError('The order total must be between Rs 0.50 and Rs 1,000,000. Please contact us for a larger order.', 422);
    }

    $orderStatement = $pdo->prepare(
        "INSERT INTO orders (checkout_token, customer_name, customer_email, customer_phone, address_line1, address_line2, city, region, postal_code, country, currency, payment_method, payment_status, subtotal, shipping, total)
         VALUES (:checkout_token, :name, :email, :phone, :address_line1, :address_line2, :city, :region, :postal_code, :country, 'pkr', 'cash_on_delivery', 'pending', :subtotal, :shipping, :total)"
    );
    $orderStatement->execute([
        ':checkout_token' => $checkoutToken,
        ':name' => $cleanCustomer['name'],
        ':email' => $cleanCustomer['email'],
        ':phone' => $cleanCustomer['phone'],
        ':address_line1' => $cleanCustomer['address_line1'],
        ':address_line2' => $cleanCustomer['address_line2'],
        ':city' => $cleanCustomer['city'],
        ':region' => $cleanCustomer['region'],
        ':postal_code' => $cleanCustomer['postal_code'],
        ':country' => $cleanCustomer['country'],
        ':subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
        ':shipping' => number_format($shippingCents / 100, 2, '.', ''),
        ':total' => number_format(($subtotalCents + $shippingCents) / 100, 2, '.', ''),
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStatement = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, product_image, color_name, unit_price, quantity, line_total)
         VALUES (:order_id, :product_id, :name, :image, :color_name, :unit_price, :quantity, :line_total)'
    );
    foreach ($lineItems as $line) {
        $itemStatement->execute([
            ':order_id' => $orderId,
            ':product_id' => $line['id'],
            ':name' => $line['name'],
            ':image' => $line['image'],
            ':color_name' => $line['color_name'],
            ':unit_price' => number_format($line['price_cents'] / 100, 2, '.', ''),
            ':quantity' => $line['quantity'],
            ':line_total' => number_format($line['price_cents'] * $line['quantity'] / 100, 2, '.', ''),
        ]);
    }
    $pdo->commit();
    orderJsonSuccess($orderId);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($exception->getCode() === '23000' && isset($checkoutToken)) {
        $existingOrder = $pdo->prepare('SELECT id FROM orders WHERE checkout_token = :checkout_token');
        $existingOrder->execute([':checkout_token' => $checkoutToken]);
        $existingOrderId = $existingOrder->fetchColumn();
        if ($existingOrderId !== false) {
            orderJsonSuccess((int) $existingOrderId);
        }
    }
    error_log('Pop Shop order creation failed: ' . $exception->getMessage());
    orderJsonError('We could not place your order. No payment was taken. Please try again.', 500);
}
