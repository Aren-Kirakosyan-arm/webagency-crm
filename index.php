<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Dashboard | WebAgency CRM';
$errors = [];
$success = '';

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_project') {
    $name = trim((string)($_POST['project_name'] ?? ''));
    $client = trim((string)($_POST['client_name'] ?? ''));
    $budgetRaw = (string)($_POST['budget'] ?? '0');
    $budget = filter_var($budgetRaw, FILTER_VALIDATE_FLOAT);
    $deadline = trim((string)($_POST['deadline'] ?? ''));

    if ($name === '' || mb_strlen($name) > 180) $errors[] = 'Enter a project name (up to 180 characters).';
    if ($client === '' || mb_strlen($client) > 150) $errors[] = 'Enter a client name (up to 150 characters).';
    if ($budget === false || $budget < 0) $errors[] = 'Budget must be a non-negative number.';
    if ($deadline !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) $errors[] = 'Enter a valid deadline.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $clientStmt = $pdo->prepare('SELECT id FROM clients WHERE name = ? LIMIT 1');
            $clientStmt->execute([$client]);
            $clientId = $clientStmt->fetchColumn();

            if (!$clientId) {
                $insertClient = $pdo->prepare('INSERT INTO clients (name) VALUES (?)');
                $insertClient->execute([$client]);
                $clientId = (int)$pdo->lastInsertId();
            }

            $stmt = $pdo->prepare(
                "INSERT INTO projects (client_id, name, budget, status, progress, deadline)
                 VALUES (:client_id, :name, :budget, 'planning', 0, :deadline)"
            );
            $stmt->execute([
                ':client_id' => $clientId,
                ':name' => $name,
                ':budget' => $budget,
                ':deadline' => $deadline !== '' ? $deadline : null,
            ]);

            $pdo->commit();
            header('Location: index.php?created=1');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Create project failed: ' . $exception->getMessage());
            $errors[] = 'Could not save the project. Check the database connection and schema.';
        }
    }
}

if (isset($_GET['created'])) $success = 'Project saved successfully.';

$projects = $pdo->query(
    'SELECT p.id, p.name, p.budget, p.status, p.progress, p.deadline, p.created_at,
            c.name AS client_name
     FROM projects p
     LEFT JOIN clients c ON c.id = p.client_id
     ORDER BY p.created_at DESC, p.id DESC'
)->fetchAll();

$activeProjects = 0;
$completedProjects = 0;
$totalBudget = 0.0;
foreach ($projects as $project) {
    if ($project['status'] === 'completed') $completedProjects++;
    else $activeProjects++;
    $totalBudget += (float)$project['budget'];
}
$clientCount = (int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
$taskCount = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status <> 'completed'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WebAgency CRM — manage clients, projects and tasks.">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a href="index.php" class="brand"><span class="brand-icon">W</span><span>WebAgency<span class="brand-light">CRM</span></span></a>
        <div class="menu-label">WORKSPACE</div>
        <nav class="navigation">
            <a href="index.php" class="nav-link active"><span>▦</span> Dashboard</a>
            <a href="#projects" class="nav-link"><span>▤</span> Projects</a>
            <a href="#clients" class="nav-link"><span>♙</span> Clients</a>
            <a href="#tasks" class="nav-link"><span>✓</span> Tasks</a>
            <a href="#analytics" class="nav-link"><span>▥</span> Analytics</a>
        </nav>
        <div class="menu-label">PREFERENCES</div>
        <nav class="navigation"><a href="#settings" class="nav-link"><span>⚙</span> Settings</a></nav>
        <div class="sidebar-bottom">
            <div class="upgrade-card"><div class="upgrade-icon">✦</div><h3>Work smarter</h3><p>Keep your clients and projects organized in one place.</p><a href="#projects">Explore projects →</a></div>
            <div class="profile"><div class="avatar">AK</div><div class="profile-info"><strong>Aren Kirakosyan</strong><span>Workspace owner</span></div></div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
            <div class="breadcrumb">Workspace <span>/</span> <strong>Dashboard</strong></div>
            <div class="topbar-right"><span class="today" id="today"></span><div class="avatar small-avatar">AK</div></div>
        </header>

        <section class="content">
            <div class="welcome-row">
                <div><div class="eyebrow">OVERVIEW</div><h1>Good to see you, Aren <span>✦</span></h1><p class="subtitle">Here's what's happening with your agency today.</p></div>
                <button class="primary-button" id="newProject"><span>＋</span> New project</button>
            </div>

            <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($errors): ?><div class="alert error"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>

            <div class="stats-grid">
                <article class="stat-card"><div class="stat-top"><span>Total project budget</span><span class="stat-icon purple">$</span></div><div class="stat-value">$<?= number_format($totalBudget, 0) ?></div><div class="stat-bottom"><span>From saved projects</span></div></article>
                <article class="stat-card"><div class="stat-top"><span>Active projects</span><span class="stat-icon blue">▤</span></div><div class="stat-value"><?= str_pad((string)$activeProjects, 2, '0', STR_PAD_LEFT) ?></div><div class="stat-bottom"><span>Not completed</span></div></article>
                <article class="stat-card"><div class="stat-top"><span>Total clients</span><span class="stat-icon orange">♙</span></div><div class="stat-value"><?= $clientCount ?></div><div class="stat-bottom"><span>Saved in database</span></div></article>
                <article class="stat-card"><div class="stat-top"><span>Open tasks</span><span class="stat-icon green">✓</span></div><div class="stat-value"><?= $taskCount ?></div><div class="stat-bottom"><span><?= $completedProjects ?> completed projects</span></div></article>
            </div>

            <div class="dashboard-grid">
                <section class="panel" id="analytics">
                    <div class="panel-header"><div><h2>Project budget overview</h2><p>Budget of your latest projects</p></div></div>
                    <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
                </section>
                <section class="panel" id="tasks">
                    <div class="panel-header"><div><h2>Getting started</h2><p>Next steps for your workspace</p></div></div>
                    <div class="task-list">
                        <div class="task-item"><span class="custom-check checked">✓</span><span class="task-text"><strong>Connect MySQL database</strong><small>Database connection configured</small></span></div>
                        <div class="task-item"><span class="custom-check"></span><span class="task-text"><strong>Add your first project</strong><small>Projects are saved in MySQL</small></span></div>
                        <div class="task-item"><span class="custom-check"></span><span class="task-text"><strong>Publish updates to GitHub</strong><small>Keep your portfolio up to date</small></span></div>
                    </div>
                    <div class="task-footer">This dashboard currently uses your local database.</div>
                </section>
            </div>

            <section class="panel projects-panel" id="projects">
                <div class="panel-header"><div><h2>Recent projects</h2><p>Projects saved in your database</p></div><button class="text-link" id="viewAll">Total: <?= count($projects) ?> projects</button></div>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>PROJECT NAME</th><th>CLIENT</th><th>STATUS</th><th>PROGRESS</th><th>BUDGET</th></tr></thead>
                        <tbody id="projectTable">
                        <?php if (!$projects): ?>
                            <tr><td colspan="5" class="empty-state">No projects yet. Click “New project” to create one.</td></tr>
                        <?php else: foreach ($projects as $project):
                            $status = (string)$project['status'];
                            $statusClass = in_array($status, ['planning','in_progress','review','completed'], true) ? $status : 'planning';
                        ?>
                            <tr>
                                <td><div class="project-name"><span class="project-logo violet"><?= e(strtoupper(substr($project['name'], 0, 1))) ?></span><span><strong><?= e($project['name']) ?></strong><small><?= $project['deadline'] ? 'Due ' . e($project['deadline']) : 'Agency project' ?></small></span></div></td>
                                <td><?= e($project['client_name'] ?? 'No client') ?></td>
                                <td><span class="status <?= e($statusClass) ?>"><?= e(ucwords(str_replace('_', ' ', $status))) ?></span></td>
                                <td><div class="progress-cell"><div class="progress-bar"><span style="width: <?= min(100, (int)$project['progress']) ?>%"></span></div><small><?= min(100, (int)$project['progress']) ?>%</small></div></td>
                                <td class="budget">$<?= number_format((float)$project['budget'], 2) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <footer class="footer"><span>© <?= date('Y') ?> WebAgency CRM</span><span>PHP · MySQL · JavaScript · CSS</span></footer>
        </section>
    </main>
</div>

<div class="modal-backdrop" id="projectModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-heading"><div><h2 id="modalTitle">Create new project</h2><p>Save a project to your MySQL database.</p></div><button class="modal-close" id="closeModal" aria-label="Close">×</button></div>
        <form method="post" action="index.php" id="projectForm">
            <input type="hidden" name="action" value="create_project">
            <label for="projectName">Project name</label><input id="projectName" name="project_name" type="text" placeholder="e.g. Website redesign" maxlength="180" required>
            <label for="clientName">Client name</label><input id="clientName" name="client_name" type="text" placeholder="e.g. John Smith" maxlength="150" required>
            <label for="projectBudget">Budget (USD)</label><input id="projectBudget" name="budget" type="number" min="0" step="0.01" placeholder="2500" required>
            <label for="projectDeadline">Deadline (optional)</label><input id="projectDeadline" name="deadline" type="date">
            <button type="submit" class="primary-button full-button">Save project</button>
        </form>
    </div>
</div>
</body>
</html>
