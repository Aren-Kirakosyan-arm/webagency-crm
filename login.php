<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = '';
$email = '';
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $plainPassword = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $plainPassword === '') {
        $error = 'Check your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id,name,email,password FROM users WHERE email=:email LIMIT 1');
        $stmt->execute(['email'=>$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($plainPassword, $user['password'])) {
            $error = 'Incorrect email or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $update = $pdo->prepare('UPDATE users SET password=:password WHERE id=:id');
                $update->execute(['password'=>password_hash($plainPassword,PASSWORD_DEFAULT),'id'=>$user['id']]);
            }
            header('Location: index.php'); exit;
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in — WebAgency CRM</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-body"><main class="auth-page"><section class="auth-card"><a class="auth-brand" href="login.php"><span class="brand-mark">W</span> WebAgency <b>CRM</b></a><h1>Welcome back!</h1><p class="auth-description">Sign in to your workspace.</p>
<?php if($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="login.php" class="auth-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Email<input name="email" type="email" maxlength="190" autocomplete="username" value="<?= e($email) ?>" required></label><label>Password<input name="password" type="password" autocomplete="current-password" required></label><button class="primary-button full-button" type="submit">Sign in</button></form><p class="auth-switch">No account? <a href="register.php">Create an account</a></p></section></main></body></html>
