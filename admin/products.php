<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
requireAdmin();
$pdo = getDatabaseConnection();
$errorMessage = '';
$formValues = ['id' => '', 'name' => '', 'category' => 'Keychains', 'price' => '', 'badge' => '', 'image' => '', 'description' => '', 'sort_order' => '0'];
$variantRows = [];
$uploadDirectory = __DIR__ . '/../uploads/products';
$colorPalette = [
    'Red' => '#EF4444',
    'Red orange' => '#F97316',
    'Orange' => '#FB923C',
    'Amber' => '#F59E0B',
    'Yellow' => '#FACC15',
    'Lime' => '#A3E635',
    'Green' => '#22C55E',
    'Emerald' => '#10B981',
    'Teal' => '#14B8A6',
    'Cyan' => '#06B6D4',
    'Sky blue' => '#38BDF8',
    'Blue' => '#3B82F6',
    'Navy' => '#1E3A8A',
    'Indigo' => '#6366F1',
    'Purple' => '#8B5CF6',
    'Violet' => '#A855F7',
    'Magenta' => '#D946EF',
    'Pink' => '#EC4899',
    'Rose' => '#F43F5E',
    'Brown' => '#8B5E3C',
    'Tan' => '#C49A6C',
    'Beige' => '#E8D5B7',
    'White' => '#FFFFFF',
    'Gray' => '#9CA3AF',
    'Black' => '#171717',
];

if (isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT) !== false) {
    $edit = $pdo->prepare("SELECT id, name, category, price, badge, image, description, sort_order FROM products WHERE id = :id AND category = 'Keychains'");
    $edit->execute([':id' => (int) $_GET['edit']]);
    $existing = $edit->fetch();
    if ($existing !== false) {
        $formValues = $existing;
        $formValues['category'] = 'Keychains';
        $variantQuery = $pdo->prepare('SELECT id, color_name, color_hex, image, sort_order FROM product_variants WHERE product_id = :product_id ORDER BY sort_order, id');
        $variantQuery->execute([':product_id' => (int) $existing['id']]);
        $variantRows = $variantQuery->fetchAll();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? null);
    $action = (string) ($_POST['action'] ?? '');
    $productId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'toggle' && $productId !== false && $productId !== null && $productId > 0) {
        $toggle = $pdo->prepare("UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id AND category = 'Keychains'");
        $toggle->execute([':id' => $productId]);
        setFlashMessage($toggle->rowCount() > 0 ? 'Product visibility updated.' : 'No product was changed.', $toggle->rowCount() > 0 ? 'success' : 'error');
        redirectTo('products.php');
    } elseif ($action === 'save') {
        foreach (array_keys($formValues) as $field) {
            if (in_array($field, ['id', 'image'], true)) {
                continue;
            }
            $formValues[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $formValues['category'] = 'Keychains';
        $formValues['id'] = $productId === false ? '' : (string) $productId;
        $existingProduct = null;
        $existingVariants = [];
        if ($formValues['id'] !== '' && (int) $formValues['id'] > 0) {
            $existingProductQuery = $pdo->prepare("SELECT image FROM products WHERE id = :id AND category = 'Keychains'");
            $existingProductQuery->execute([':id' => (int) $formValues['id']]);
            $existingImage = $existingProductQuery->fetchColumn();
            if ($existingImage === false) {
                $errorMessage = 'The selected product could not be found.';
            } else {
                $formValues['image'] = (string) $existingImage;
                $variantQuery = $pdo->prepare('SELECT id, color_name, color_hex, image, sort_order FROM product_variants WHERE product_id = :product_id');
                $variantQuery->execute([':product_id' => (int) $formValues['id']]);
                foreach ($variantQuery->fetchAll() as $variant) {
                    $existingVariants[(int) $variant['id']] = $variant;
                }
            }
        }

        $postedVariants = $_POST['variants'] ?? [];
        if (!is_array($postedVariants) || count($postedVariants) > 8) {
            $errorMessage = 'Add no more than 8 color options to a product.';
            $postedVariants = [];
        }
        $variantUploads = $_FILES['variant_images'] ?? [];
        if (!is_array($variantUploads)) {
            $variantUploads = [];
        }
        $normalizedVariants = [];
        $colorNames = [];
        foreach ($postedVariants as $index => $variant) {
            if (!is_array($variant)) {
                $errorMessage = 'Check the color option details and try again.';
                break;
            }
            $variantId = filter_var($variant['id'] ?? '', FILTER_VALIDATE_INT);
            $colorName = trim((string) ($variant['color_name'] ?? ''));
            $colorHex = strtoupper(trim((string) ($variant['color_hex'] ?? '')));
            $existingVariant = $variantId !== false && $variantId !== null ? ($existingVariants[(int) $variantId] ?? null) : null;
            $colorKey = strtolower($colorName);
            if (($variantId !== false && $variantId !== null && $variantId !== 0 && $existingVariant === null)
                || $colorName === '' || strlen($colorName) > 50 || isset($colorNames[$colorKey])
                || !preg_match('/\A#[0-9A-F]{6}\z/', $colorHex)) {
                $errorMessage = 'Each color needs a unique name and a valid color swatch.';
                break;
            }
            $colorNames[$colorKey] = true;
            $fileUpload = productImageUpload($variantUploads, $index);
            $variantImage = is_array($existingVariant) ? (string) $existingVariant['image'] : '';
            if ($fileUpload !== null && $fileUpload['error'] !== UPLOAD_ERR_NO_FILE) {
                $variantImage = '';
            }
            $normalizedVariants[] = [
                'id' => is_array($existingVariant) ? (int) $existingVariant['id'] : 0,
                'color_name' => $colorName,
                'color_hex' => $colorHex,
                'image' => $variantImage,
                'sort_order' => count($normalizedVariants),
                'upload' => $fileUpload !== null && $fileUpload['error'] !== UPLOAD_ERR_NO_FILE ? $fileUpload : null,
            ];
        }
        $variantRows = $normalizedVariants;
        $priceValue = $formValues['price'];
        $mainUpload = $_FILES['image'] ?? [];
        $hasMainImage = $formValues['image'] !== '' || (is_array($mainUpload) && ($mainUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
        $hasVariantPhoto = false;
        foreach ($normalizedVariants as $variant) {
            if ($variant['upload'] !== null || $variant['image'] !== '') {
                $hasVariantPhoto = true;
                break;
            }
        }
        if ($errorMessage === '' && ($formValues['name'] === '' || strlen($formValues['name']) > 160
            || strlen($formValues['badge']) > 40
            || strlen($formValues['description']) > 255
            || (!$hasMainImage && !$hasVariantPhoto)
            || ($formValues['image'] !== '' && productImageUrl($formValues['image']) === ''))) {
            $errorMessage = 'Complete every field and add a product photo or at least one color photo.';
        } elseif ($errorMessage === '' && (!is_numeric($priceValue) || (float) $priceValue < 0.50 || (float) $priceValue > 1000000 || !preg_match('/\A\d{1,7}(?:\.\d{1,2})?\z/', $priceValue))) {
            $errorMessage = 'Enter a price from Rs 0.50 to Rs 1,000,000 with no more than two decimal places.';
        } elseif ($errorMessage === '' && filter_var($formValues['sort_order'], FILTER_VALIDATE_INT) === false) {
            $errorMessage = 'Sort order must be a whole number.';
        } elseif ($errorMessage === '' && $formValues['id'] !== '' && (int) $formValues['id'] < 1) {
            $errorMessage = 'The selected product could not be found.';
        }

        if ($errorMessage === '') {
            $savedImagePaths = [];
            try {
                $baseUpload = $_FILES['image'] ?? null;
                if (is_array($baseUpload) && ($baseUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $formValues['image'] = saveProductImageUpload($baseUpload, $uploadDirectory);
                    $savedImagePaths[] = __DIR__ . '/../' . $formValues['image'];
                }

                foreach ($variantRows as &$variantRow) {
                    if ($variantRow['upload'] !== null) {
                        $variantRow['image'] = saveProductImageUpload($variantRow['upload'], $uploadDirectory);
                        $savedImagePaths[] = __DIR__ . '/../' . $variantRow['image'];
                    } elseif ($variantRow['image'] === '') {
                        throw new RuntimeException('Choose a photo for every new color option.');
                    }
                    unset($variantRow['upload']);
                }
                unset($variantRow);
                if ($formValues['image'] === '' && $variantRows !== []) {
                    $formValues['image'] = $variantRows[0]['image'];
                }

                $pdo->beginTransaction();
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
                    $productId = (int) $pdo->lastInsertId();
                } else {
                    $productId = (int) $formValues['id'];
                    $values[':id'] = $productId;
                    $update = $pdo->prepare("UPDATE products SET name = :name, category = :category, price = :price, badge = :badge, image = :image, description = :description, sort_order = :sort_order WHERE id = :id AND category = 'Keychains'");
                    $update->execute($values);
                }

                $keptVariantIds = [];
                foreach ($variantRows as $variantRow) {
                    $variantValues = [
                        ':product_id' => $productId,
                        ':color_name' => $variantRow['color_name'],
                        ':color_hex' => $variantRow['color_hex'],
                        ':image' => $variantRow['image'],
                        ':sort_order' => $variantRow['sort_order'],
                    ];
                    if ($variantRow['id'] > 0) {
                        $variantValues[':id'] = $variantRow['id'];
                        $updateVariant = $pdo->prepare('UPDATE product_variants SET color_name = :color_name, color_hex = :color_hex, image = :image, sort_order = :sort_order WHERE id = :id AND product_id = :product_id');
                        $updateVariant->execute($variantValues);
                        $keptVariantIds[] = $variantRow['id'];
                    } else {
                        $insertVariant = $pdo->prepare('INSERT INTO product_variants (product_id, color_name, color_hex, image, sort_order) VALUES (:product_id, :color_name, :color_hex, :image, :sort_order)');
                        $insertVariant->execute($variantValues);
                    }
                }

                foreach (array_keys($existingVariants) as $existingVariantId) {
                    if (!in_array($existingVariantId, $keptVariantIds, true)) {
                        $deleteVariant = $pdo->prepare('DELETE FROM product_variants WHERE id = :id AND product_id = :product_id');
                        $deleteVariant->execute([':id' => $existingVariantId, ':product_id' => $productId]);
                    }
                }
                $pdo->commit();
                setFlashMessage($formValues['id'] === '' ? 'Product added to your collection.' : 'Product details updated.');
                redirectTo('products.php');
            } catch (RuntimeException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($savedImagePaths as $savedImagePath) {
                    if (is_file($savedImagePath) && !unlink($savedImagePath)) {
                        error_log('Pop Shop could not remove an unused product image: ' . $savedImagePath);
                    }
                }
                $errorMessage = $exception->getMessage();
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($savedImagePaths as $savedImagePath) {
                    if (is_file($savedImagePath) && !unlink($savedImagePath)) {
                        error_log('Pop Shop could not remove an unused product image: ' . $savedImagePath);
                    }
                }
                error_log('Pop Shop product save failed: ' . $exception->getMessage());
                $errorMessage = 'The product could not be saved. Please try again.';
            }
        }
    } else {
        $errorMessage = 'That product action is not valid.';
    }
}

$products = $pdo->query("SELECT id, name, category, price, badge, image, description, sort_order, is_active FROM products WHERE category = 'Keychains' ORDER BY is_active DESC, sort_order ASC, id DESC")->fetchAll();
adminHeader('Products', 'products');
?>
<div class="admin-columns">
    <section class="admin-panel">
        <div class="panel-heading"><div><p class="eyebrow"><?= $formValues['id'] !== '' ? 'EDIT AN ITEM' : 'GROW YOUR COLLECTION' ?></p><h2><?= $formValues['id'] !== '' ? 'Edit product' : 'Add a product' ?></h2></div></div>
        <?php if ($errorMessage !== ''): ?><p class="auth-error" role="alert"><?= escapeHtml($errorMessage) ?></p><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= escapeHtml($formValues['id']) ?>">
            <label>Product name<input name="name" maxlength="160" value="<?= escapeHtml($formValues['name']) ?>" required></label>
            <div class="admin-form-row"><label>Category<input value="Keychains" readonly><input type="hidden" name="category" value="Keychains"></label><label>Price (PKR)<input name="price" type="number" min="0.50" max="1000000" step="0.01" value="<?= escapeHtml($formValues['price']) ?>" required></label></div>
            <div class="admin-form-row"><label>Badge <span>(optional)</span><input name="badge" maxlength="40" value="<?= escapeHtml($formValues['badge']) ?>" placeholder="NEW ARRIVAL"></label><label>Display order<input name="sort_order" type="number" step="1" value="<?= escapeHtml($formValues['sort_order']) ?>" required></label></div>
            <label>Main product photo<input name="image" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" <?= $formValues['id'] === '' && $variantRows === [] ? 'required' : '' ?>><span>Optional if you add at least one color photo. Otherwise this becomes the default image. JPEG, PNG, or WebP; maximum 8 MB.</span></label>
            <section class="variant-editor" data-variant-editor aria-labelledby="variant-editor-title">
                <div class="variant-editor-heading"><div><h3 id="variant-editor-title">Color options</h3><p>Add a name, swatch, and photo for each color.</p></div><button class="admin-secondary-button" type="button" data-add-variant>Add a color</button></div>
                <div class="variant-rows" data-variant-rows>
                    <?php foreach ($variantRows as $variantIndex => $variant): ?>
                        <fieldset class="variant-row" data-variant-row>
                            <legend>Color option <?= (int) $variantIndex + 1 ?></legend>
                            <input type="hidden" name="variants[<?= (int) $variantIndex ?>][id]" value="<?= (int) ($variant['id'] ?? 0) ?>">
                            <div class="admin-form-row"><label>Color name<input name="variants[<?= (int) $variantIndex ?>][color_name]" maxlength="50" value="<?= escapeHtml($variant['color_name'] ?? '') ?>" placeholder="Choose a color below or type a name" required></label><label>Custom color<input name="variants[<?= (int) $variantIndex ?>][color_hex]" type="color" value="<?= escapeHtml($variant['color_hex'] ?? '#ED3D95') ?>" required><span>Pick any shade not shown below.</span></label></div>
                            <div class="color-palette-wrap"><p>Quick color palette</p><div class="color-palette" data-color-palette role="group" aria-label="Quick color choices"></div></div>
                            <label>Photo for this color<input name="variant_images[<?= (int) $variantIndex ?>]" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" <?= (int) ($variant['id'] ?? 0) > 0 ? '' : 'required' ?>><span><?= (int) ($variant['id'] ?? 0) > 0 ? 'Choose a replacement or leave empty to keep this color photo.' : 'Take a photo or choose one from your device. Required for each new color.' ?></span></label>
                            <?php if (!empty($variant['image'])): ?><img class="variant-image-preview" src="<?= escapeHtml(productImageUrl((string) $variant['image'], '../')) ?><?= str_starts_with((string) $variant['image'], 'photo-') ? '?auto=format&amp;fit=crop&amp;w=180&amp;q=72' : '' ?>" alt="Current <?= escapeHtml($variant['color_name']) ?> color photo"><?php endif; ?>
                            <button class="variant-remove" type="button" data-remove-variant>Remove this color</button>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
                <p class="variant-limit-note">Up to 8 colors per product. Each option and its photo will appear inside the same product card.</p>
            </section>
            <template data-color-palette-template>
                <?php foreach ($colorPalette as $paletteName => $paletteHex): ?>
                    <button class="palette-color" type="button" data-palette-name="<?= escapeHtml($paletteName) ?>" data-palette-hex="<?= escapeHtml($paletteHex) ?>" style="--palette-color: <?= escapeHtml($paletteHex) ?>" aria-label="<?= escapeHtml($paletteName) ?>" title="<?= escapeHtml($paletteName) ?>"></button>
                <?php endforeach; ?>
            </template>
            <label>Description<textarea name="description" maxlength="255" rows="3" required><?= escapeHtml($formValues['description']) ?></textarea></label>
            <div class="admin-form-actions"><button class="admin-primary-button" type="submit"><?= $formValues['id'] !== '' ? 'Save product' : 'Add product' ?></button><?php if ($formValues['id'] !== ''): ?><a class="admin-secondary-button" href="products.php">Cancel edit</a><?php endif; ?></div>
        </form>
    </section>
    <section class="admin-panel product-admin-list">
        <div class="panel-heading"><div><p class="eyebrow">YOUR CATALOG</p><h2><?= count($products) ?> products</h2></div></div>
        <?php if ($products === []): ?><p class="admin-empty">No keychains yet. Add your first keychain using the form.</p><?php endif; ?>
        <?php foreach ($products as $product): ?>
            <article class="admin-product">
                <img src="<?= escapeHtml(productImageUrl((string) $product['image'], '../')) ?><?= str_starts_with((string) $product['image'], 'photo-') ? '?auto=format&amp;fit=crop&amp;w=180&amp;q=72' : '' ?>" alt="">
                <div class="admin-product-details"><p><?= escapeHtml($product['category']) ?> · Rs <?= number_format((float) $product['price'], 2) ?></p><h3><?= escapeHtml($product['name']) ?></h3><span class="status-pill <?= (int) $product['is_active'] === 1 ? 'active' : 'inactive' ?>"><?= (int) $product['is_active'] === 1 ? 'Live' : 'Hidden' ?></span></div>
                <div class="admin-product-actions"><a class="table-action" href="products.php?edit=<?= (int) $product['id'] ?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button class="table-action" type="submit"><?= (int) $product['is_active'] === 1 ? 'Hide' : 'Restore' ?></button></form></div>
            </article>
        <?php endforeach; ?>
    </section>
</div>
<script src="../assets/js/admin-product-variants.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-product-variants.js') ?>" defer></script>
<?php adminFooter(); ?>
