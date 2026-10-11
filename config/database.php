<?php
declare(strict_types=1);

function getDatabaseConnection(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $database = getenv('DB_NAME') ?: 'noir_form';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function getStoreBaseUrl(): string
{
    $configuredUrl = getenv('STORE_BASE_URL');
    if ($configuredUrl !== false && $configuredUrl !== '') {
        return rtrim($configuredUrl, '/');
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/\A[a-zA-Z0-9.\-:\[\]]+\z/', $host)) {
        throw new RuntimeException('The request host is invalid. Set STORE_BASE_URL to your public store URL.');
    }

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isSecure ? 'https' : 'http';
    $applicationPath = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $applicationPath = $applicationPath === DIRECTORY_SEPARATOR ? '' : str_replace('\\', '/', $applicationPath);

    return $scheme . '://' . $host . rtrim($applicationPath, '/');
}
