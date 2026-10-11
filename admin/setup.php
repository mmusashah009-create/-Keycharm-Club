<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$pdo = getDatabaseConnection();
$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalRequest = in_array($remoteAddress, ['127.0.0.1', '::1'], true);
$errorMessage = '';
$username = '';

function adminAccountExists(PDO $pdo): bool
{
    return (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
}

if (adminAccountExists($pdo)) {
    redirectTo('login.php');
}

if (!$isLocalRequest) {
    http_response_code(403);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? null);
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    if (!$isLocalRequest) {
        $errorMessage = 'For security, create the first administrator from the computer running this store.';
    } elseif (!preg_match('/\A[a-zA-Z0-9_.@+-]{3,50}\z/', $username)) {
        $errorMessage = 'Choose a username between 3 and 50 letters, numbers, dots, dashes, underscores, or @ signs.';
    } elseif (strlen($password) < 12 || strlen($password) > 200) {
        $errorMessage = 'Choose a password between 12 and 200 characters.';
    } elseif (!hash_equals($password, $confirmation)) {
        $errorMessage = 'The passwords do not match.';
    } else {
        $lockStatement = $pdo->query("SELECT GET_LOCK('noir_form_admin_setup', 5)");
        $lockAcquired = (int) $lockStatement->fetchColumn() === 1;
        if (!$lockAcquired) {
            $errorMessage = 'Admin setup is busy. Please try again in a moment.';
        } else {
            try {
                if (adminAccountExists($pdo)) {
                    $errorMessage = 'An admin account has already been created. Sign in instead.';
                } else {
                    $insert = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)');
                    $insert->execute([':username' => $username, ':password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int) $pdo->lastInsertId();
                    $_SESSION['admin_username'] = $username;
                    renewCsrfToken();
                    setFlashMessage('Admin account created. Welcome to your store dashboard.');
                }
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errorMessage = 'That username is already in use. Choose another.';
                } else {
                    throw $exception;
                }
            } finally {
                $pdo->query("SELECT RELEASE_LOCK('noir_form_admin_setup')");
            }
            if (isset($_SESSION['admin_id'])) {
                redirectTo('index.php');
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create your admin account — NOIR &amp; FORM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-ink text-paper auth-page">
    <main class="auth-card">
        <a class="wordmark" href="../index.php">NOIR<span>&amp;</span>FORM<span class="wordmark-period">.</span></a>
        <p class="eyebrow">ONE-TIME STORE SETUP</p>
        <h1>Create your <em>admin.</em></h1>
        <p class="auth-intro">Create the first administrator account on this computer. You choose the admin password; this one-time form automatically closes after the account is created.</p>
        <?php if ($errorMessage !== ''): ?><p class="auth-error" role="alert"><?= escapeHtml($errorMessage) ?></p><?php endif; ?>
        <?php if (!$isLocalRequest): ?><p class="auth-error" role="alert">For security, first-admin setup is available only from the computer running this store.</p><?php endif; ?>
        <form method="post" class="admin-form auth-form">
            <input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>">
            <label>Username or email<input name="username" value="<?= escapeHtml($username) ?>" autocomplete="username" minlength="3" maxlength="50" required></label>
            <label>Password<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="200" required><span>Use at least 12 characters.</span></label>
            <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="200" required></label>
            <button class="admin-primary-button" type="submit" <?= !$isLocalRequest ? 'disabled' : '' ?>>Create secure admin account</button>
        </form>
        <a class="auth-back" href="../index.php">← Back to storefront</a>
    </main>
</body>
</html>
