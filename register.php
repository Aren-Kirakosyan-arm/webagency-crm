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

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $plainPassword = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        $error = 'Укажите имя длиной до 100 символов.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Укажите корректный email.';
    } elseif (strlen($plainPassword) < 10 || strlen($plainPassword) > 4096) {
        $error = 'Пароль должен содержать минимум 10 символов.';
    } elseif ($plainPassword !== $confirmPassword) {
        $error = 'Пароли не совпадают.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            $error = 'Этот email уже зарегистрирован.';
        } else {
            $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
            try {
                $insert = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
                $insert->execute(['name' => $name, 'email' => $email, 'password' => $hash]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: index.php');
                exit;
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $error = $exception->getCode() === '23000'
                    ? 'Этот email уже зарегистрирован.'
                    : 'Не удалось создать аккаунт. Попробуйте позже.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Регистрация — WebAgency CRM</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-body">
<main class="auth-page"><section class="auth-card">
    <a class="auth-brand" href="login.php"><span class="brand-mark">W</span> WebAgency <b>CRM</b></a>
    <h1>Создать аккаунт</h1><p class="auth-description">Зарегистрируйтесь, чтобы управлять проектами и клиентами.</p>
    <?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="register.php" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Имя<input name="name" type="text" maxlength="100" autocomplete="name" value="<?= e($name) ?>" required></label>
        <label>Email<input name="email" type="email" maxlength="190" autocomplete="email" value="<?= e($email) ?>" required></label>
        <label>Пароль <small>Минимум 10 символов</small><input name="password" type="password" minlength="10" maxlength="4096" autocomplete="new-password" required></label>
        <label>Повторите пароль<input name="confirm_password" type="password" minlength="10" maxlength="4096" autocomplete="new-password" required></label>
        <button class="primary-button full-button" type="submit">Зарегистрироваться</button>
    </form>
    <p class="auth-switch">Уже есть аккаунт? <a href="login.php">Войти</a></p>
</section></main>
</body></html>
