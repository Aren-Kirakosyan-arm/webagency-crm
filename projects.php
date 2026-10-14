<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$statuses = [
    'planning' => 'Планирование',
    'in_progress' => 'В работе',
    'review' => 'На проверке',
    'completed' => 'Завершён',
];

$error = '';
$success = '';

$clients = $pdo->query(
    'SELECT id, name, company FROM clients ORDER BY name ASC'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $clientRaw = trim((string) ($_POST['client_id'] ?? ''));
    $budgetRaw = trim((string) ($_POST['budget'] ?? '0'));
    $deadlineRaw = trim((string) ($_POST['deadline'] ?? ''));
    $status = (string) ($_POST['status'] ?? 'planning');

    if ($action === 'delete') {
        if (!$id || $id < 1) {
            $error = 'Некорректный проект.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM projects WHERE id = :id');
            $stmt->execute(['id' => $id]);

            header('Location: projects.php?deleted=1');
            exit;
        }
    } elseif (in_array($action, ['create', 'update'], true)) {
        $clientId = null;

        if ($clientRaw !== '') {
            $validatedClient = filter_var($clientRaw, FILTER_VALIDATE_INT);

            if (!$validatedClient || $validatedClient < 1) {
                $error = 'Выберите корректного клиента.';
            } else {
                $clientCheck = $pdo->prepare(
                    'SELECT id FROM clients WHERE id = :id'
                );
                $clientCheck->execute(['id' => $validatedClient]);

                if (!$clientCheck->fetch()) {
                    $error = 'Выбранный клиент не найден.';
                } else {
                    $clientId = $validatedClient;
                }
            }
        }

        if (!$error && ($name === '' || mb_strlen($name) > 150)) {
            $error = 'Название проекта обязательно (до 150 символов).';
        }

        if (!$error && mb_strlen($description) > 10000) {
            $error = 'Описание слишком длинное.';
        }

        if (
            !$error &&
            (!is_numeric($budgetRaw) ||
                (float) $budgetRaw < 0 ||
                (float) $budgetRaw > 9999999999.99)
        ) {
            $error = 'Введите корректный бюджет.';
        }

        if (!$error && !array_key_exists($status, $statuses)) {
            $error = 'Выберите корректный статус.';
        }

        if (!$error && $deadlineRaw !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $deadlineRaw);

            if (!$date || $date->format('Y-m-d') !== $deadlineRaw) {
                $error = 'Укажите корректный срок выполнения.';
            }
        }

        if (!$error && $action === 'update' && (!$id || $id < 1)) {
            $error = 'Некорректный идентификатор проекта.';
        }

        if (!$error) {
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO projects
                            (client_id, name, description, budget, status, progress, deadline)
                         VALUES
                            (:client_id, :name, :description, :budget, :status, :progress, :deadline)'
                    );

                    $stmt->execute([
                        'client_id' => $clientId,
                        'name' => $name,
                        'description' => $description !== '' ? $description : null,
                        'budget' => $budgetRaw,
                        'status' => $status,
                        'progress' => $status === 'completed' ? 100 : 0,
                        'deadline' => $deadlineRaw !== '' ? $deadlineRaw : null,
                    ]);

                    header('Location: projects.php?created=1');
                    exit;
                }

                $exists = $pdo->prepare(
                    'SELECT id FROM projects WHERE id = :id'
                );
                $exists->execute(['id' => $id]);

                if (!$exists->fetch()) {
                    $error = 'Проект не найден.';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE projects
                         SET client_id = :client_id,
                             name = :name,
                             description = :description,
                             budget = :budget,
                             status = :status,
                             progress = :progress,
                             deadline = :deadline
                         WHERE id = :id'
                    );

                    $stmt->execute([
                        'client_id' => $clientId,
                        'name' => $name,
                        'description' => $description !== '' ? $description : null,
                        'budget' => $budgetRaw,
                        'status' => $status,
                        'progress' => $status === 'completed' ? 100 : 0,
                        'deadline' => $deadlineRaw !== '' ? $deadlineRaw : null,
                        'id' => $id,
                    ]);

                    header('Location: projects.php?updated=1');
                    exit;
                }
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $error = 'Не удалось сохранить проект. Проверьте журнал ошибок.';
            }
        }
    }
}

if (isset($_GET['created'])) {
    $success = 'Проект успешно создан.';
} elseif (isset($_GET['updated'])) {
    $success = 'Изменения проекта сохранены.';
} elseif (isset($_GET['deleted'])) {
    $success = 'Проект удалён.';
}

$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');

$sql = 'SELECT p.id, p.client_id, p.name, p.description, p.budget,
               p.status, p.progress, p.deadline, p.created_at,
               c.name AS client_name, c.company AS client_company
        FROM projects p
        LEFT JOIN clients c ON c.id = p.client_id
        WHERE (p.name LIKE :q1
            OR COALESCE(c.name, \'\') LIKE :q2
            OR COALESCE(c.company, \'\') LIKE :q3)';

$params = [
    'q1' => '%' . $search . '%',
    'q2' => '%' . $search . '%',
    'q3' => '%' . $search . '%',
];

if (array_key_exists($statusFilter, $statuses)) {
    $sql .= ' AND p.status = :status';
    $params['status'] = $statusFilter;
}

$sql .= ' ORDER BY p.created_at DESC, p.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$editProject = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $editProject = $stmt->fetch() ?: null;

    if (!$editProject) {
        $error = 'Проект для редактирования не найден.';
    }
}

$user = current_user();
$totalBudget = array_sum(array_map(
    static fn(array $project): float => (float) $project['budget'],
    $projects
));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Проекты — WebAgency CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .project-toolbar {
            display:flex; gap:12px; flex-wrap:wrap; align-items:center;
            margin-bottom:18px;
        }
        .project-toolbar input, .project-toolbar select {
            border:1px solid #dfe4ee; border-radius:9px;
            padding:12px; background:#fff; color:#172033;
        }
        .project-toolbar input { flex:1; min-width:180px; }
        .project-toolbar select { min-width:160px; }
        .project-metrics {
            display:grid; grid-template-columns:repeat(3,minmax(0,1fr));
            gap:14px; margin-bottom:20px;
        }
        .project-metric {
            background:#fff; border:1px solid #e8ebf2;
            border-radius:12px; padding:18px;
        }
        .project-metric span { display:block; color:#778198; font-size:12px; }
        .project-metric strong { display:block; font-size:23px; margin-top:9px; }
        .project-actions { display:flex; align-items:center; gap:8px; }
        .edit-link, .delete-button {
            border:0; border-radius:7px; padding:8px 10px;
            font-size:12px; text-decoration:none; cursor:pointer;
        }
        .edit-link { background:#eef0ff; color:#596cf2; }
        .delete-button { background:#fff0f0; color:#c34646; }
        .project-description { color:#8a93a6; white-space:normal; min-width:130px; max-width:240px; }
        .status-select { border:1px solid #e0e5ef; border-radius:6px; padding:6px; }
        .project-form .full-field { grid-column:1/-1; }
        @media(max-width:700px) {
            .project-metrics { grid-template-columns:1fr; }
            .project-toolbar { align-items:stretch; flex-direction:column; }
            .project-toolbar input, .project-toolbar select { width:100%; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php">
            <span class="brand-mark">W</span><span>WebAgency <b>CRM</b></span>
        </a>
        <nav class="nav">
            <a class="nav-link" href="index.php">▦ <span>Dashboard</span></a>
            <a class="nav-link" href="clients.php">♙ <span>Клиенты</span></a>
            <a class="nav-link active" href="projects.php">▤ <span>Проекты</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-mini">
                <div class="avatar"><?= e(mb_strtoupper(mb_substr((string) ($user['name'] ?? ''), 0, 1))) ?></div>
                <div>
                    <strong><?= e($user['name'] ?? '') ?></strong>
                    <small><?= e($user['email'] ?? '') ?></small>
                </div>
            </div>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="logout-button" type="submit">↪ Выйти</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><span class="eyebrow">WORKSPACE</span><h1>Проекты</h1></div>
        </header>

        <section class="welcome-row">
            <div>
                <h2>Управление проектами</h2>
                <p>Бюджеты, клиенты, статусы и сроки вашей веб-студии.</p>
            </div>
            <a class="primary-button" href="projects.php#project-form" style="text-decoration:none">＋ Новый проект</a>
        </section>

        <?php if ($error): ?>
                <div class="alert error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
                <div class="alert success" role="status"><?= e($success) ?></div>
        <?php endif; ?>

        <section class="project-metrics">
            <article class="project-metric">
                <span>Найдено проектов</span>
                <strong><?= count($projects) ?></strong>
            </article>
            <article class="project-metric">
                <span>Общий бюджет списка</span>
                <strong><?= number_format($totalBudget, 0, ',', ' ') ?> ֏</strong>
            </article>
            <article class="project-metric">
                <span>Завершено</span>
                <strong><?= count(array_filter($projects, static fn(array $p): bool => $p['status'] === 'completed')) ?></strong>
            </article>
        </section>

        <section class="panel" id="project-form">
            <div class="panel-heading">
                <div>
                    <h3><?= $editProject ? 'Редактировать проект' : 'Создать проект' ?></h3>
                    <p>Заполните данные и привяжите проект к клиенту.</p>
                </div>
                <?php if ($editProject): ?>
                        <a class="secondary-button" href="projects.php" style="text-decoration:none">Отмена</a>
                <?php endif; ?>
            </div>

            <form method="post" action="projects.php<?= $editProject ? '?edit=' . (int) $editProject['id'] : '' ?>" class="project-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="<?= $editProject ? 'update' : 'create' ?>">
                <?php if ($editProject): ?>
                        <input type="hidden" name="id" value="<?= (int) $editProject['id'] ?>">
                <?php endif; ?>

                <label>Название проекта
                    <input name="name" maxlength="150" required
                           value="<?= e($editProject['name'] ?? '') ?>"
                           placeholder="Например, Интернет-магазин">
                </label>

                <label>Клиент
                    <select name="client_id">
                        <option value="">Без привязки</option>
                        <?php foreach ($clients as $client): ?>
                                <option value="<?= (int) $client['id'] ?>"
                                    <?= (string) ($editProject['client_id'] ?? '') === (string) $client['id'] ? 'selected' : '' ?>>
                                    <?= e($client['name']) ?>    <?= !empty($client['company']) ? ' — ' . e($client['company']) : '' ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Бюджет (֏)
                    <input name="budget" type="number" min="0" max="9999999999.99"
                           step="0.01" required value="<?= e($editProject['budget'] ?? '0') ?>">
                </label>

                <label>Срок выполнения
                    <input name="deadline" type="date" value="<?= e($editProject['deadline'] ?? '') ?>">
                </label>

                <label>Статус
                    <select name="status">
                        <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?= e($value) ?>"
                                    <?= ($editProject['status'] ?? 'planning') === $value ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="full-field">Описание
                    <textarea name="description" rows="3" maxlength="10000"
                              placeholder="Что нужно сделать в рамках проекта"><?= e($editProject['description'] ?? '') ?></textarea>
                </label>

                <div class="modal-actions">
                    <button class="primary-button" type="submit">
                        <?= $editProject ? 'Сохранить изменения' : 'Создать проект' ?>
                    </button>
                </div>
            </form>
            <?php if (!$clients): ?>
                    <p class="alert error" style="margin-top:15px">
                        Клиентов пока нет. Сначала добавь клиента на странице
                        <a href="clients.php">«Клиенты»</a>, чтобы привязать его к проекту.
                    </p>
            <?php endif; ?>
        </section>

        <section class="panel" style="margin-top:20px">
            <div class="panel-heading">
                <div>
                    <h3>Все проекты</h3>
                    <p>Редактируй данные или удали ненужный проект.</p>
                </div>
            </div>

            <form method="get" action="projects.php" class="project-toolbar">
                <input type="search" name="q" value="<?= e($search) ?>"
                       placeholder="Поиск по проекту или клиенту">
                <select name="status">
                    <option value="">Все статусы</option>
                    <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                    <?php endforeach; ?>
                </select>
                <button class="secondary-button" type="submit">Фильтровать</button>
                <a class="text-button" href="projects.php">Сбросить</a>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Проект</th>
                        <th>Клиент</th>
                        <th>Статус</th>
                        <th>Бюджет</th>
                        <th>Дедлайн</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projects as $project): ?>
                            <tr>
                                <td>
                                    <strong><?= e($project['name']) ?></strong>
                                    <small class="subline project-description">
                                        <?= e($project['description'] ?: 'Без описания') ?>
                                    </small>
                                </td>
                                <td>
                                    <?= e($project['client_name'] ?? '—') ?>
                                    <?php if (!empty($project['client_company'])): ?>
                                            <small class="subline"><?= e($project['client_company']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= e($project['status']) ?>">
                                        <?= e($statuses[$project['status']] ?? $project['status']) ?>
                                    </span>
                                </td>
                                <td><?= number_format((float) $project['budget'], 0, ',', ' ') ?> ֏</td>
                                <td>
                                    <?php if ($project['deadline']): ?>
                                            <?= e(date('d.m.Y', strtotime($project['deadline']))) ?>
                                    <?php else: ?>
                                            —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="project-actions">
                                        <a class="edit-link" href="projects.php?edit=<?= (int) $project['id'] ?>#project-form">Изменить</a>
                                        <form method="post" action="projects.php"
                                              onsubmit="return confirm('Точно удалить этот проект?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
                                            <button class="delete-button" type="submit">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                    <?php endforeach; ?>
                    <?php if (!$projects): ?>
                            <tr><td colspan="6">Проекты не найдены. Создай первый проект выше.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <footer class="footer">© <?= date('Y') ?> WebAgency CRM <span>Управление веб-проектами</span></footer>
    </main>
</div>
</body>
</html>
