<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (isset($_SESSION['admin_id'])) {
    redirectTo('index.php');
}

$pdo = getDatabaseConnection();
$adminSetupComplete = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
$errorMessage = '';
$username = '';
$attempts = $_SESSION['admin_login_attempts'] ?? ['count' => 0, 'started' => time()];
$lockRemaining = max(0, 300 - (time() - (int) $attempts['started']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? null);
    if ($lockRemaining > 0 && (int) $attempts['count'] >= 5) {
        $errorMessage = 'Too many sign-in attempts. Please wait before trying again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $statement = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
        $statement->execute([':username' => $username]);
        $admin = $statement->fetch();

        if ($admin !== false && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            unset($_SESSION['admin_login_attempts']);
            renewCsrfToken();
            redirectTo('index.php');
        }

        if (time() - (int) $attempts['started'] >= 300) {
            $attempts = ['count' => 0, 'started' => time()];
        }
        $attempts['count'] = (int) $attempts['count'] + 1;
        $_SESSION['admin_login_attempts'] = $attempts;
        $errorMessage = $attempts['count'] >= 5
            ? 'Too many sign-in attempts. Please wait five minutes before trying again.'
            : 'The username or password is incorrect.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in — NOIR &amp; FORM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-ink text-paper auth-page">
    <main class="auth-card">
        <a class="wordmark" href="../index.php">NOIR<span>&amp;</span>FORM<span class="wordmark-period">.</span></a>
        <p class="eyebrow">THE CONTROL ROOM</p>
        <h1>Good to have<br>you <em>back.</em></h1>
        <p class="auth-intro">Sign in to manage your products and orders.</p>
        <?php if ($errorMessage !== ''): ?><p class="auth-error" role="alert"><?= escapeHtml($errorMessage) ?></p><?php endif; ?>
        <form method="post" class="admin-form auth-form">
            <input type="hidden" name="csrf_token" value="<?= adminCsrfToken() ?>">
            <label>Username or email<input name="username" value="<?= escapeHtml($username) ?>" autocomplete="username" required></label>
            <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
            <button class="admin-primary-button" type="submit">Sign in securely</button>
        </form>
        <?php if (!$adminSetupComplete): ?><a class="auth-back" href="setup.php">Create the first admin account ↗</a><?php endif; ?>
        <a class="auth-back" href="../index.php">← Back to storefront</a>
    </main>
</body>
</html>
