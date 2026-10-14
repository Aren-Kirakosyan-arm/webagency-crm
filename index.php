<?php
$pageTitle = 'Dashboard | WebAgency CRM';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WebAgency CRM — manage clients, projects and business performance.">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/app.js" defer></script>
</head>
<body>

<div class="app">
    <aside class="sidebar" id="sidebar">
        <a href="index.php" class="brand">
            <span class="brand-icon">W</span>
            <span>WebAgency<span class="brand-light">CRM</span></span>
        </a>

        <div class="menu-label">WORKSPACE</div>

        <nav class="navigation">
            <a href="index.php" class="nav-link active">
                <span>▦</span> Dashboard
            </a>
            <a href="#projects" class="nav-link">
                <span>▤</span> Projects
            </a>
            <a href="#clients" class="nav-link">
                <span>♙</span> Clients
            </a>
            <a href="#tasks" class="nav-link">
                <span>✓</span> Tasks
            </a>
            <a href="#analytics" class="nav-link">
                <span>▥</span> Analytics
            </a>
        </nav>

        <div class="menu-label">PREFERENCES</div>

        <nav class="navigation">
            <a href="#settings" class="nav-link">
                <span>⚙</span> Settings
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="upgrade-card">
                <div class="upgrade-icon">✦</div>
                <h3>Work smarter</h3>
                <p>Keep all your projects organized in one place.</p>
                <a href="#projects">Explore projects →</a>
            </div>

            <div class="profile">
                <div class="avatar">AK</div>
                <div class="profile-info">
                    <strong>Aren Kirakosyan</strong>
                    <span>Administrator</span>
                </div>
                <span class="profile-dots">•••</span>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>

            <div class="breadcrumb">
                Workspace <span>/</span> <strong>Dashboard</strong>
            </div>

            <div class="topbar-right">
                <span class="today" id="today"></span>
                <button class="icon-button" aria-label="Notifications">♧</button>
                <div class="avatar small-avatar">AK</div>
            </div>
        </header>

        <section class="content">
            <div class="welcome-row">
                <div>
                    <div class="eyebrow">OVERVIEW</div>
                    <h1>Good to see you, Aren <span>✦</span></h1>
                    <p class="subtitle">Here's what's happening with your agency today.</p>
                </div>

                <button class="primary-button" id="newProject">
                    <span>＋</span> New project
                </button>
            </div>

            <div class="stats-grid">
                <article class="stat-card">
                    <div class="stat-top">
                        <span>Total revenue</span>
                        <span class="stat-icon purple">＄</span>
                    </div>
                    <div class="stat-value">$24,680</div>
                    <div class="stat-bottom">
                        <span class="trend">↗ 12.8%</span>
                        <span>vs. last month</span>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Active projects</span>
                        <span class="stat-icon blue">▤</span>
                    </div>
                    <div class="stat-value" id="activeCount">08</div>
                    <div class="stat-bottom">
                        <span class="trend">↗ 2</span>
                        <span>new this month</span>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Total clients</span>
                        <span class="stat-icon orange">♙</span>
                    </div>
                    <div class="stat-value">124</div>
                    <div class="stat-bottom">
                        <span class="trend">↗ 8.2%</span>
                        <span>vs. last month</span>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Completed tasks</span>
                        <span class="stat-icon green">✓</span>
                    </div>
                    <div class="stat-value">86%</div>
                    <div class="stat-bottom">
                        <span class="trend">↗ 4.6%</span>
                        <span>completion rate</span>
                    </div>
                </article>
            </div>

            <div class="dashboard-grid">
                <section class="panel revenue-panel" id="analytics">
                    <div class="panel-header">
                        <div>
                            <h2>Revenue overview</h2>
                            <p>Track your agency's performance</p>
                        </div>
                        <select id="chartPeriod" aria-label="Chart period">
                            <option value="monthly">Last 7 months</option>
                            <option value="weekly">Last 7 days</option>
                        </select>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </section>

                <section class="panel tasks-panel" id="tasks">
                    <div class="panel-header">
                        <div>
                            <h2>Today's tasks</h2>
                            <p>Stay on top of your work</p>
                        </div>
                        <span class="task-counter" id="taskCounter">3 tasks</span>
                    </div>

                    <div class="task-list">
                        <label class="task-item">
                            <input type="checkbox">
                            <span class="custom-check"></span>
                            <span class="task-text">
                                <strong>Review homepage design</strong>
                                <small>Website redesign</small>
                            </span>
                            <span class="priority high">High</span>
                        </label>

                        <label class="task-item">
                            <input type="checkbox">
                            <span class="custom-check"></span>
                            <span class="task-text">
                                <strong>Send proposal to client</strong>
                                <small>Marketing website</small>
                            </span>
                            <span class="priority medium">Medium</span>
                        </label>

                        <label class="task-item">
                            <input type="checkbox">
                            <span class="custom-check"></span>
                            <span class="task-text">
                                <strong>Update project progress</strong>
                                <small>Mobile application</small>
                            </span>
                            <span class="priority low">Low</span>
                        </label>
                    </div>

                    <div class="task-footer">Check tasks off as you complete them.</div>
                </section>
            </div>

            <section class="panel projects-panel" id="projects">
                <div class="panel-header">
                    <div>
                        <h2>Recent projects</h2>
                        <p>Manage and track your ongoing work</p>
                    </div>
                    <a href="#projects" class="text-link" id="viewAll">View all projects →</a>
                </div>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>PROJECT NAME</th>
                                <th>CLIENT</th>
                                <th>STATUS</th>
                                <th>PROGRESS</th>
                                <th>BUDGET</th>
                            </tr>
                        </thead>
                        <tbody id="projectTable">
                            <tr>
                                <td>
                                    <div class="project-name">
                                        <span class="project-logo violet">S</span>
                                        <span><strong>Shopify redesign</strong><small>Website development</small></span>
                                    </div>
                                </td>
                                <td>Sarah Johnson</td>
                                <td><span class="status in-progress">In progress</span></td>
                                <td><div class="progress-cell"><div class="progress-bar"><span style="width:75%"></span></div><small>75%</small></div></td>
                                <td class="budget">$4,500</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="project-name">
                                        <span class="project-logo blue-bg">N</span>
                                        <span><strong>Nova landing page</strong><small>UI/UX design</small></span>
                                    </div>
                                </td>
                                <td>Michael Chen</td>
                                <td><span class="status review">In review</span></td>
                                <td><div class="progress-cell"><div class="progress-bar"><span style="width:90%"></span></div><small>90%</small></div></td>
                                <td class="budget">$2,800</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="project-name">
                                        <span class="project-logo orange-bg">F</span>
                                        <span><strong>Fintech dashboard</strong><small>Web application</small></span>
                                    </div>
                                </td>
                                <td>David Wilson</td>
                                <td><span class="status in-progress">In progress</span></td>
                                <td><div class="progress-cell"><div class="progress-bar"><span style="width:45%"></span></div><small>45%</small></div></td>
                                <td class="budget">$6,200</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="project-name">
                                        <span class="project-logo green-bg">A</span>
                                        <span><strong>Agency portfolio</strong><small>Website development</small></span>
                                    </div>
                                </td>
                                <td>Emma Davis</td>
                                <td><span class="status completed">Completed</span></td>
                                <td><div class="progress-cell"><div class="progress-bar"><span style="width:100%"></span></div><small>100%</small></div></td>
                                <td class="budget">$3,400</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="footer">
                <span>© 2026 WebAgency CRM</span>
                <span>Built with PHP, JavaScript &amp; CSS</span>
            </footer>
        </section>
    </main>
</div>

<div class="modal-backdrop" id="projectModal" aria-hidden="true">
    <div class="modal">
        <div class="modal-heading">
            <div>
                <h2>Create new project</h2>
                <p>Add a project to your workspace.</p>
            </div>
            <button class="modal-close" id="closeModal" aria-label="Close">×</button>
        </div>

        <form id="projectForm">
            <label for="projectName">Project name</label>
            <input id="projectName" name="projectName" type="text" placeholder="e.g. Website redesign" required maxlength="80">

            <label for="clientName">Client name</label>
            <input id="clientName" name="clientName" type="text" placeholder="e.g. John Smith" required maxlength="80">

            <label for="projectBudget">Budget (USD)</label>
            <input id="projectBudget" name="projectBudget" type="number" min="0" step="0.01" placeholder="2500" required>

            <button type="submit" class="primary-button full-button">Create project</button>
        </form>
    </div>
</div>

</body>
</html>