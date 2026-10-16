<?php
declare(strict_types=1);

function productImageUrl(string $image, string $uploadPrefix = ''): string
{
    if (preg_match('/\Auploads\/products\/[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $image)) {
        return $uploadPrefix . $image;
    }

    if (preg_match('/\Aphoto-[a-zA-Z0-9-]{6,100}\z/', $image)) {
        return 'https://images.unsplash.com/' . rawurlencode($image);
    }

    return '';
}

function saveProductImageUpload(array $upload, string $directory): string
{
    if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The photo could not be uploaded. Choose a JPEG, PNG, or WebP image up to 8 MB.');
    }
    if (!isset($upload['tmp_name'], $upload['size']) || !is_uploaded_file((string) $upload['tmp_name'])
        || (int) $upload['size'] < 1 || (int) $upload['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Choose a valid photo no larger than 8 MB.');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file((string) $upload['tmp_name']);
    $imageInfo = @getimagesize((string) $upload['tmp_name']);
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!is_string($mimeType) || !isset($allowedTypes[$mimeType]) || $imageInfo === false
        || $imageInfo[0] > 12000 || $imageInfo[1] > 12000 || $imageInfo[0] * $imageInfo[1] > 40000000
        || ($imageInfo['mime'] ?? '') !== $mimeType) {
        throw new RuntimeException('Use a valid JPEG, PNG, or WebP photo. Images must be no larger than 12,000 pixels on either side.');
    }

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The photo upload folder could not be created. Check the server folder permissions.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
    if (!move_uploaded_file((string) $upload['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The photo could not be saved. Check the server folder permissions and try again.');
    }

    return 'uploads/products/' . $filename;
}

function productImageUpload(array $files, int|string $index): ?array
{
    if (!isset($files['error'][$index])) {
        return null;
    }

    return [
        'name' => $files['name'][$index] ?? '',
        'type' => $files['type'][$index] ?? '',
        'tmp_name' => $files['tmp_name'][$index] ?? '',
        'error' => $files['error'][$index],
        'size' => $files['size'][$index] ?? 0,
    ];
}

function productColorHex(string $color): string
{
    return preg_match('/\A#[0-9A-Fa-f]{6}\z/', $color) ? strtoupper($color) : '#EEEEEE';
}
