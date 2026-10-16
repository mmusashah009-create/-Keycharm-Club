<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/product-images.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    session_name('NoirFormSession');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
header('Cache-Control: no-store, private');

function escapeHtml(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function renewCsrfToken(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrfToken(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireValidCsrfToken(?string $token): void
{
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        exit('The request could not be verified. Refresh the page and try again.');
    }
}

function requireAdmin(): void
{
    if (!isset($_SESSION['admin_id'], $_SESSION['admin_username'])) {
        header('Location: login.php');
        exit;
    }
}

function adminCsrfToken(): string
{
    return escapeHtml(csrfToken());
}

function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function setFlashMessage(string $message, string $type = 'success'): void
{
    $_SESSION['admin_flash'] = ['message' => $message, 'type' => $type];
}

function consumeFlashMessage(): ?array
{
    if (!isset($_SESSION['admin_flash'])) {
        return null;
    }

    $message = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);

    return $message;
}
