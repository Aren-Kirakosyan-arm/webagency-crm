document.addEventListener('DOMContentLoaded', () => {
    const today = document.getElementById('today');

    if (today) {
        today.textContent = new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        }).format(new Date());
    }

    // Mobile navigation
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');

    menuToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
    });

    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 760) {
                sidebar?.classList.remove('open');
            }
        });
    });

    // Tasks
    const taskCheckboxes = document.querySelectorAll('.task-item input');
    const taskCounter = document.getElementById('taskCounter');

    function updateTasks() {
        const remaining = [...taskCheckboxes]
            .filter(checkbox => !checkbox.checked).length;

        if (taskCounter) {
            taskCounter.textContent = `${remaining} task${remaining === 1 ? '' : 's'} left`;
        }
    }

    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateTasks);
    });

    updateTasks();

    // Revenue chart
    const canvas = document.getElementById('revenueChart');

    if (canvas && typeof Chart !== 'undefined') {
        const chartData = {
            monthly: {
                labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                values: [3200, 4100, 3600, 5400, 4700, 6300, 7200]
            },
            weekly: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                values: [800, 1200, 950, 1600, 1400, 1900, 2200]
            }
        };

        const chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: chartData.monthly.labels,
                datasets: [{
                    label: 'Revenue',
                    data: chartData.monthly.values,
                    borderColor: '#7563eb',
                    backgroundColor: context => {
                        const gradient = context.chart.ctx.createLinearGradient(
                            0, 0, 0, 260
                        );
                        gradient.addColorStop(0, 'rgba(117, 99, 235, 0.22)');
                        gradient.addColorStop(1, 'rgba(117, 99, 235, 0)');
                        return gradient;
                    },
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#7563eb',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#202237',
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: item => '$' + item.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#999caf', font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false, dash: [4, 4] },
                        grid: { color: '#f0f1f6' },
                        ticks: {
                            color: '#999caf',
                            font: { size: 10 },
                            callback: value => '$' + (value / 1000) + 'k'
                        }
                    }
                }
            }
        });

        document.getElementById('chartPeriod')?.addEventListener('change', event => {
            const data = chartData[event.target.value];
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.update();
        });
    }

    // New project modal
    const modal = document.getElementById('projectModal');
    const openModal = document.getElementById('newProject');
    const closeModal = document.getElementById('closeModal');
    const projectForm = document.getElementById('projectForm');
    const projectTable = document.getElementById('projectTable');
    const activeCount = document.getElementById('activeCount');

    function hideModal() {
        modal?.classList.remove('open');
        modal?.setAttribute('aria-hidden', 'true');
    }

    openModal?.addEventListener('click', () => {
        modal?.classList.add('open');
        modal?.setAttribute('aria-hidden', 'false');
        document.getElementById('projectName')?.focus();
    });

    closeModal?.addEventListener('click', hideModal);

    modal?.addEventListener('click', event => {
        if (event.target === modal) hideModal();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') hideModal();
    });

    projectForm?.addEventListener('submit', event => {
        event.preventDefault();

        const name = document.getElementById('projectName').value.trim();
        const client = document.getElementById('clientName').value.trim();
        const budget = Number(document.getElementById('projectBudget').value);

        if (!name || !client || !Number.isFinite(budget) || budget < 0) {
            return;
        }

        const row = document.createElement('tr');
        const nameCell = document.createElement('td');
        const projectName = document.createElement('div');
        projectName.className = 'project-name';

        const logo = document.createElement('span');
        logo.className = 'project-logo violet';
        logo.textContent = name.charAt(0).toUpperCase();

        const details = document.createElement('span');
        const title = document.createElement('strong');
        title.textContent = name;

        const subtitle = document.createElement('small');
        subtitle.textContent = 'New project';

        details.append(title, subtitle);
        projectName.append(logo, details);
        nameCell.append(projectName);

        const clientCell = document.createElement('td');
        clientCell.textContent = client;

        const statusCell = document.createElement('td');
        const status = document.createElement('span');
        status.className = 'status in-progress';
        status.textContent = 'In progress';
        statusCell.append(status);

        const progressCell = document.createElement('td');
        const progress = document.createElement('div');
        progress.className = 'progress-cell';

        const bar = document.createElement('div');
        bar.className = 'progress-bar';

        const fill = document.createElement('span');
        fill.style.width = '0%';
        bar.append(fill);

        const percentage = document.createElement('small');
        percentage.textContent = '0%';
        progress.append(bar, percentage);
        progressCell.append(progress);

        const budgetCell = document.createElement('td');
        budgetCell.className = 'budget';
        budgetCell.textContent = '$' + budget.toLocaleString('en-US', {
            maximumFractionDigits: 2
        });

        row.append(nameCell, clientCell, statusCell, progressCell, budgetCell);
        projectTable?.prepend(row);

        if (activeCount) {
            const count = Number(activeCount.textContent) || 0;
            activeCount.textContent = String(count + 1).padStart(2, '0');
        }

        projectForm.reset();
        hideModal();
    });
});