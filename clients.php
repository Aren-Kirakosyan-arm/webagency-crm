
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $company = trim((string)($_POST['company'] ?? ''));

        if ($name === '' || mb_strlen($name) > 150) {
            $error = 'Введите имя клиента (до 150 символов).';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Укажите корректный email.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO clients (name, email, phone, company)
                 VALUES (:name, :email, :phone, :company)'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'company' => $company !== '' ? $company : null,
            ]);

            header('Location: clients.php?created=1');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $error = 'Некорректный клиент.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM clients WHERE id = :id');
            $stmt->execute(['id' => $id]);
            header('Location: clients.php?deleted=1');
            exit;
        }
    }
}

if (isset($_GET['created'])) $success = 'Клиент добавлен.';
if (isset($_GET['deleted'])) $success = 'Клиент удалён.';

$search = trim((string)($_GET['q'] ?? ''));
$stmt = $pdo->prepare(
    'SELECT c.id, c.name, c.email, c.phone, c.company, c.created_at,
            COUNT(p.id) AS project_count
     FROM clients c
     LEFT JOIN projects p ON p.client_id = c.id
     WHERE c.name LIKE :q1
        OR COALESCE(c.email, "") LIKE :q2
        OR COALESCE(c.company, "") LIKE :q3
     GROUP BY c.id, c.name, c.email, c.phone, c.company, c.created_at
     ORDER BY c.created_at DESC'
);
$like = '%' . $search . '%';
$stmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like]);
$clients = $stmt->fetchAll();

$user = current_user();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Клиенты — WebAgency CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php">
            <span class="brand-mark">W</span><span>WebAgency <b>CRM</b></span>
        </a>
        <nav class="nav">
            <a class="nav-link" href="index.php">▦ <span>Dashboard</span></a>
            <a class="nav-link active" href="clients.php">♙ <span>Клиенты</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-mini">
                <div class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
                <div><strong><?= e($user['name']) ?></strong><small><?= e($user['email']) ?></small></div>
            </div>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="logout-button" type="submit">↪ Выйти</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><span class="eyebrow">WORKSPACE</span><h1>Клиенты</h1></div>
        </header>

        <section class="welcome-row">
            <div><h2>База клиентов</h2><p>Контакты и информация о ваших заказчиках.</p></div>
        </section>

        <?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success" role="status"><?= e($success) ?></div><?php endif; ?>

        <section class="panel">
            <div class="panel-heading">
                <div><h3>Добавить клиента</h3><p>Заполните контактную информацию.</p></div>
            </div>
            <form method="post" action="clients.php" class="project-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <label>Имя клиента<input name="name" maxlength="150" required></label>
                <label>Email<input name="email" type="email" maxlength="190"></label>
                <label>Телефон<input name="phone" type="tel" maxlength="50"></label>
                <label>Компания<input name="company" maxlength="150"></label>
                <div class="modal-actions"><button class="primary-button" type="submit">Добавить клиента</button></div>
            </form>
        </section>

        <section class="panel" style="margin-top:20px">
            <div class="panel-heading">
                <div><h3>Все клиенты (<?= count($clients) ?>)</h3><p>Найденные записи в базе данных.</p></div>
            </div>
            <form method="get" action="clients.php" class="search-form">
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Поиск по имени, email или компании">
                <button class="secondary-button" type="submit">Найти</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Клиент</th><th>Контакты</th><th>Компания</th><th>Проекты</th><th>Добавлен</th><th>Действия</th></tr></thead>
                    <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><strong><?= e($client['name']) ?></strong></td>
                            <td><?= e($client['email'] ?? '—') ?><small class="subline"><?= e($client['phone'] ?? '') ?></small></td>
                            <td><?= e($client['company'] ?? '—') ?></td>
                            <td><?= (int)$client['project_count'] ?></td>
                            <td><?= e(date('d.m.Y', strtotime($client['created_at']))) ?></td>
                            <td>
                                <form method="post" action="clients.php" onsubmit="return confirm('Удалить этого клиента?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$client['id'] ?>">
                                    <button class="delete-button" type="submit">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$clients): ?><tr><td colspan="6">Клиенты не найдены.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
