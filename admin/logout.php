<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the sign-out button to end your admin session.');
}

requireValidCsrfToken($_POST['csrf_token'] ?? null);
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $cookie['path'],
    'domain' => $cookie['domain'],
    'secure' => $cookie['secure'],
    'httponly' => $cookie['httponly'],
    'samesite' => $cookie['samesite'] ?? 'Lax',
]);
session_destroy();
redirectTo('login.php');
