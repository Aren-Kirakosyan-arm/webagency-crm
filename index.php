<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$error = '';
$success = isset($_GET['created']) ? 'Проект успешно создан.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $projectName = trim((string)($_POST['project_name'] ?? ''));
    $clientName = trim((string)($_POST['client_name'] ?? ''));
    $budgetRaw = trim((string)($_POST['budget'] ?? '0'));
    $description = trim((string)($_POST['description'] ?? ''));
    $deadlineRaw = trim((string)($_POST['deadline'] ?? ''));

    if ($projectName === '' || mb_strlen($projectName) > 150) {
        $error = 'Введите название проекта (до 150 символов).';
    } elseif ($clientName === '' || mb_strlen($clientName) > 150) {
        $error = 'Введите имя клиента (до 150 символов).';
    } elseif (!is_numeric($budgetRaw) || (float)$budgetRaw < 0 || (float)$budgetRaw > 999999999) {
        $error = 'Укажите корректный бюджет.';
    } else {
        try {
            $pdo->beginTransaction();
            $clientStmt = $pdo->prepare('SELECT id FROM clients WHERE name = :name LIMIT 1');
            $clientStmt->execute(['name' => $clientName]);
            $client = $clientStmt->fetch();

            if ($client) {
                $clientId = (int)$client['id'];
            } else {
                $insertClient = $pdo->prepare('INSERT INTO clients (name) VALUES (:name)');
                $insertClient->execute(['name' => $clientName]);
                $clientId = (int)$pdo->lastInsertId();
            }

            $insertProject = $pdo->prepare(
                'INSERT INTO projects (client_id, name, description, budget, status, progress, deadline)
                 VALUES (:client_id, :name, :description, :budget, :status, 0, :deadline)'
            );
            $insertProject->execute([
                'client_id' => $clientId,
                'name' => $projectName,
                'description' => $description,
                'budget' => (float)$budgetRaw,
                'status' => 'planning',
                'deadline' => $deadlineRaw !== '' ? $deadlineRaw : null,
            ]);
            $pdo->commit();
            header('Location: index.php?created=1');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($exception->getMessage());
            $error = 'Не удалось сохранить проект. Проверьте базу данных и попробуйте снова.';
        }
    }
}

$stats = [
    'clients' => (int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
    'projects' => (int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'tasks' => (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status <> 'completed'")->fetchColumn(),
    'completed' => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn(),
];
$projects = $pdo->query(
    'SELECT p.id, p.name, p.description, p.budget, p.status, p.progress, p.deadline, p.created_at,
            c.name AS client_name
     FROM projects p LEFT JOIN clients c ON c.id = p.client_id
     ORDER BY p.created_at DESC LIMIT 10'
)->fetchAll();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — WebAgency CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="index.php"><span class="brand-mark">W</span><span>WebAgency <b>CRM</b></span></a>
        <nav class="nav">
            <a class="nav-link active" href="index.php">▦ <span>Dashboard</span></a>
            <a class="nav-link" href="#projects">▤ <span>Проекты</span></a>
            <a class="nav-link" href="#clients">♙ <span>Клиенты</span></a>
            <a class="nav-link" href="#tasks">☑ <span>Задачи</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-mini"><div class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div><div><strong><?= e($user['name']) ?></strong><small><?= e($user['email']) ?></small></div></div>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="logout-button" type="submit">↪ Выйти</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="Открыть меню">☰</button>
            <div><span class="eyebrow">WORKSPACE</span><h1>Dashboard</h1></div>
            <div class="topbar-right"><span class="status-dot"></span><span>Локальная CRM</span><div class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div></div>
        </header>

        <section class="welcome-row">
            <div><h2>Добро пожаловать, <?= e($user['name']) ?> 👋</h2><p>Вот что происходит в вашей веб-студии сегодня.</p></div>
            <button class="primary-button" id="newProjectBtn" type="button">＋ Новый проект</button>
        </section>

        <?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success" role="status"><?= e($success) ?></div><?php endif; ?>

        <section class="stats-grid">
            <article class="stat-card"><div class="stat-icon purple">▤</div><span>Всего проектов</span><strong><?= $stats['projects'] ?></strong><small>Все проекты в системе</small></article>
            <article class="stat-card"><div class="stat-icon blue">♙</div><span>Клиенты</span><strong><?= $stats['clients'] ?></strong><small>Добавленные клиенты</small></article>
            <article class="stat-card"><div class="stat-icon orange">☑</div><span>Открытые задачи</span><strong><?= $stats['tasks'] ?></strong><small>Ещё требуют внимания</small></article>
            <article class="stat-card"><div class="stat-icon green">✓</div><span>Завершено</span><strong><?= $stats['completed'] ?></strong><small>Успешно выполненные проекты</small></article>
        </section>

        <section class="content-grid">
            <article class="panel project-panel" id="projects">
                <div class="panel-heading"><div><h3>Последние проекты</h3><p>Недавно добавленные проекты вашей команды</p></div><button class="text-button" id="newProjectBtn2" type="button">＋ Добавить</button></div>
                <?php if (!$projects): ?>
                    <div class="empty-state"><div class="empty-icon">▤</div><strong>Пока нет проектов</strong><p>Создайте первый проект, чтобы начать работу.</p><button class="primary-button" id="emptyNewProjectBtn" type="button">Создать проект</button></div>
                <?php else: ?>
                    <div class="table-wrap"><table><thead><tr><th>Проект</th><th>Клиент</th><th>Статус</th><th>Прогресс</th><th>Бюджет</th></tr></thead><tbody>
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $statusLabels = ['planning' => 'Планирование', 'in_progress' => 'В работе', 'review' => 'На проверке', 'completed' => 'Завершён'];
                        ?>
                        <tr>
                            <td><strong><?= e($project['name']) ?></strong><small class="subline"><?= e($project['description'] ?: 'Без описания') ?></small></td>
                            <td><?= e($project['client_name'] ?? '—') ?></td>
                            <td><span class="badge <?= e($project['status']) ?>"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span></td>
                            <td><div class="progress-line"><span style="width: <?= max(0, min(100, (int)$project['progress'])) ?>%"></span></div><small><?= (int)$project['progress'] ?>%</small></td>
                            <td><?= number_format((float)$project['budget'], 0, ',', ' ') ?> ֏</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </article>
            <article class="panel chart-panel">
                <div class="panel-heading"><div><h3>Обзор проектов</h3><p>Распределение по статусам</p></div></div>
                <div class="chart-wrap"><canvas id="projectsChart"></canvas></div>
                <div class="chart-legend"><span><i class="legend-dot planning-dot"></i> Планирование</span><span><i class="legend-dot progress-dot"></i> В работе</span><span><i class="legend-dot done-dot"></i> Завершено</span></div>
            </article>
        </section>
        <footer class="footer">© <?= date('Y') ?> WebAgency CRM <span>Сделано для эффективной работы</span></footer>
    </main>
</div>

<div class="modal-backdrop" id="projectModal" aria-hidden="true">
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-heading"><div><h2 id="modalTitle">Новый проект</h2><p>Заполните основные данные проекта.</p></div><button type="button" class="close-modal" id="closeModal" aria-label="Закрыть">×</button></div>
        <form method="post" action="index.php" class="project-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Название проекта<input name="project_name" maxlength="150" required placeholder="Например, Landing page"></label>
            <label>Имя клиента<input name="client_name" maxlength="150" required placeholder="Например, Arman Petrosyan"></label>
            <label>Бюджет (֏)<input name="budget" type="number" min="0" max="999999999" step="1" value="0" required></label>
            <label>Срок выполнения<input name="deadline" type="date"></label>
            <label class="full-field">Описание<textarea name="description" rows="3" placeholder="Краткое описание проекта"></textarea></label>
            <div class="modal-actions"><button type="button" class="secondary-button" id="cancelModal">Отмена</button><button class="primary-button" type="submit">Сохранить проект</button></div>
        </form>
    </section>
</div>
<script>
window.crmChartData = <?= json_encode([
    'planning' => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status='planning'")->fetchColumn(),
    'in_progress' => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status='in_progress'")->fetchColumn(),
    'completed' => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status='completed'")->fetchColumn(),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
