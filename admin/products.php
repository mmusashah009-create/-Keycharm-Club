<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
requireAdmin();
$pdo = getDatabaseConnection();
$errorMessage = '';
$formValues = ['id' => '', 'name' => '', 'category' => '', 'price' => '', 'badge' => '', 'image' => '', 'description' => '', 'sort_order' => '0'];

if (isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT) !== false) {
    $edit = $pdo->prepare('SELECT id, name, category, price, badge, image, description, sort_order FROM products WHERE id = :id');
    $edit->execute([':id' => (int) $_GET['edit']]);
    $existing = $edit->fetch();
    if ($existing !== false) {
        $formValues = $existing;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? null);
    $action = (string) ($_POST['action'] ?? '');
    $productId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'toggle' && $productId !== false && $productId !== null && $productId > 0) {
        $toggle = $pdo->prepare('UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id');
        $toggle->execute([':id' => $productId]);
        setFlashMessage($toggle->rowCount() > 0 ? 'Product visibility updated.' : 'No product was changed.', $toggle->rowCount() > 0 ? 'success' : 'error');
        redirectTo('products.php');
    } elseif ($action === 'save') {
        foreach (array_keys($formValues) as $field) {
            if ($field === 'id') {
                continue;
            }
            $formValues[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $formValues['id'] = $productId === false ? '' : (string) $productId;
        $priceValue = $formValues['price'];
        if ($formValues['name'] === '' || strlen($formValues['name']) > 160
            || $formValues['category'] === '' || strlen($formValues['category']) > 80
            || strlen($formValues['badge']) > 40
            || strlen($formValues['description']) > 255
            || !preg_match('/\Aphoto-[a-zA-Z0-9-]{6,100}\z/', $formValues['image'])) {
            $errorMessage = 'Please complete every field. Image must be an Unsplash photo ID such as photo-1234567890.';
        } elseif (!is_numeric($priceValue) || (float) $priceValue < 0.50 || (float) $priceValue > 1000000 || !preg_match('/\A\d{1,7}(?:\.\d{1,2})?\z/', $priceValue)) {
            $errorMessage = 'Enter a price from $0.50 to $1,000,000 with no more than two decimal places.';
        } elseif (filter_var($formValues['sort_order'], FILTER_VALIDATE_INT) === false) {
            $errorMessage = 'Sort order must be a whole number.';
        } elseif ($formValues['id'] !== '' && (int) $formValues['id'] < 1) {
            $errorMessage = 'The selected product could not be found.';
        } else {
            $values = [
                ':name' => $formValues['name'],
                ':category' => $formValues['category'],
                ':price' => number_format((float) $priceValue, 2, '.', ''),
                ':badge' => $formValues['badge'],
                ':image' => $formValues['image'],
                ':description' => $formValues['description'],
                ':sort_order' => (int) $formValues['sort_order'],
            ];
            if ($formValues['id'] === '') {
                $insert = $pdo->prepare('INSERT INTO products (name, category, price, badge, image, description, sort_order) VALUES (:name, :category, :price, :badge, :image, :description, :sort_order)');
                $insert->execute($values);
                setFlashMessage('Product added to your collection.');
            } else {
                $values[':id'] = (int) $formValues['id'];
                $update = $pdo->prepare('UPDATE products SET name = :name, category = :category, price = :price, badge = :badge, image = :image, description = :description, sort_order = :sort_order WHERE id = :id');
                $update->execute($values);
                setFlashMessage('Product details updated.');
            }
            redirectTo('products.php');
        }
    } else {
        $errorMessage = 'That product action is not valid.';
    }
}

$products = $pdo->query('SELECT id, name, category, price, badge, image, description, sort_order, is_active FROM products ORDER BY is_active DESC, sort_order ASC, id DESC')->fetchAll();
adminHeader('Products', 'products');
?>
<div class="admin-columns">
    <section class="admin-panel">
        <div class="panel-heading"><div><p class="eyebrow"><?= $formValues['id'] !== '' ? 'EDIT AN ITEM' : 'GROW YOUR COLLECTION' ?></p><h2><?= $formValues['id'] !== '' ? 'Edit product' : 'Add a product' ?></h2></div></div>
        <?php if ($errorMessage !== ''): ?><p class="auth-error" role="alert"><?= escapeHtml($errorMessage) ?></p><?php endif; ?>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= escapeHtml($formValues['id']) ?>">
            <label>Product name<input name="name" maxlength="160" value="<?= escapeHtml($formValues['name']) ?>" required></label>
            <div class="admin-form-row"><label>Category<input name="category" maxlength="80" value="<?= escapeHtml($formValues['category']) ?>" placeholder="Clothing, Accessories…" required></label><label>Price (USD)<input name="price" type="number" min="0.50" max="1000000" step="0.01" value="<?= escapeHtml($formValues['price']) ?>" required></label></div>
            <div class="admin-form-row"><label>Badge <span>(optional)</span><input name="badge" maxlength="40" value="<?= escapeHtml($formValues['badge']) ?>" placeholder="NEW ARRIVAL"></label><label>Display order<input name="sort_order" type="number" step="1" value="<?= escapeHtml($formValues['sort_order']) ?>" required></label></div>
            <label>Unsplash photo ID<input name="image" maxlength="120" value="<?= escapeHtml($formValues['image']) ?>" placeholder="photo-1539109136881-3be0616acf4b" required><span>Enter the photo ID only, without the images.unsplash.com domain.</span></label>
            <label>Description<textarea name="description" maxlength="255" rows="3" required><?= escapeHtml($formValues['description']) ?></textarea></label>
            <div class="admin-form-actions"><button class="admin-primary-button" type="submit"><?= $formValues['id'] !== '' ? 'Save product' : 'Add product' ?></button><?php if ($formValues['id'] !== ''): ?><a class="admin-secondary-button" href="products.php">Cancel edit</a><?php endif; ?></div>
        </form>
    </section>
    <section class="admin-panel product-admin-list">
        <div class="panel-heading"><div><p class="eyebrow">YOUR CATALOG</p><h2><?= count($products) ?> products</h2></div></div>
        <?php if ($products === []): ?><p class="admin-empty">Your collection is empty. Add your first product to get started.</p><?php endif; ?>
        <?php foreach ($products as $product): ?>
            <article class="admin-product">
                <img src="https://images.unsplash.com/<?= escapeHtml($product['image']) ?>?auto=format&amp;fit=crop&amp;w=180&amp;q=72" alt="">
                <div class="admin-product-details"><p><?= escapeHtml($product['category']) ?> · $<?= number_format((float) $product['price'], 2) ?></p><h3><?= escapeHtml($product['name']) ?></h3><span class="status-pill <?= (int) $product['is_active'] === 1 ? 'active' : 'inactive' ?>"><?= (int) $product['is_active'] === 1 ? 'Live' : 'Hidden' ?></span></div>
                <div class="admin-product-actions"><a class="table-action" href="products.php?edit=<?= (int) $product['id'] ?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button class="table-action" type="submit"><?= (int) $product['is_active'] === 1 ? 'Hide' : 'Restore' ?></button></form></div>
            </article>
        <?php endforeach; ?>
    </section>
</div>
<?php adminFooter(); ?>
