<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$name = '';
$email = '';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $plainPassword = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        $error = 'Enter a name up to 100 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($plainPassword) < 10 || strlen($plainPassword) > 4096) {
        $error = 'Password must contain at least 10 characters.';
    } elseif ($plainPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            $error = 'This email is already registered.';
        } else {
            try {
                $insert = $pdo->prepare(
                    'INSERT INTO users(name, email, password) VALUES(:name, :email, :password)'
                );
                $insert->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($plainPassword, PASSWORD_DEFAULT),
                ]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: index.php');
                exit;
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $error = $exception->getCode() === '23000'
                    ? 'This email is already registered.'
                    : 'Could not create the account. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Registration — WebAgency CRM</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-body"><main class="auth-page"><section class="auth-card"><a class="auth-brand" href="login.php"><span class="brand-mark">W</span> WebAgency <b>CRM</b></a><h1>Create account</h1><p class="auth-description">Register to manage projects and clients.</p>
<?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="register.php" class="auth-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Name<input name="name" maxlength="100" autocomplete="name" value="<?= e($name) ?>" required></label><label>Email<input name="email" type="email" maxlength="190" autocomplete="email" value="<?= e($email) ?>" required></label><label>Password <small>At least 10 characters</small><input name="password" type="password" minlength="10" maxlength="4096" autocomplete="new-password" required></label><label>Confirm password<input name="confirm_password" type="password" minlength="10" maxlength="4096" autocomplete="new-password" required></label><button class="primary-button full-button" type="submit">Create an account</button></form><p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p></section></main></body></html>
