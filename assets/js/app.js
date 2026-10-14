document.addEventListener('DOMContentLoaded', () => {
    const today = document.getElementById('today');
    if (today) {
        today.textContent = new Intl.DateTimeFormat('en-US', {
            month: 'short', day: 'numeric', year: 'numeric'
        }).format(new Date());
    }

    const sidebar = document.getElementById('sidebar');
    document.getElementById('menuToggle')?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
    });
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 760) sidebar?.classList.remove('open');
        });
    });

    const modal = document.getElementById('projectModal');
    const form = document.getElementById('projectForm');
    const hideModal = () => {
        modal?.classList.remove('open');
        modal?.setAttribute('aria-hidden', 'true');
    };

    document.getElementById('newProject')?.addEventListener('click', () => {
        modal?.classList.add('open');
        modal?.setAttribute('aria-hidden', 'false');
        document.getElementById('projectName')?.focus();
    });
    document.getElementById('closeModal')?.addEventListener('click', hideModal);
    modal?.addEventListener('click', event => {
        if (event.target === modal) hideModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') hideModal();
    });

    // The form submits to PHP; PHP validates and saves the project in MySQL.
    form?.addEventListener('submit', event => {
        const name = document.getElementById('projectName').value.trim();
        const client = document.getElementById('clientName').value.trim();
        const budget = Number(document.getElementById('projectBudget').value);
        if (!name || !client || !Number.isFinite(budget) || budget < 0) {
            event.preventDefault();
            alert('Please enter a project name, client, and a valid non-negative budget.');
        }
    });

    const canvas = document.getElementById('revenueChart');
    if (canvas && typeof Chart !== 'undefined') {
        const rows = [...document.querySelectorAll('#projectTable tr')];
        const labels = [];
        const values = [];
        rows.forEach(row => {
            const name = row.querySelector('.project-name strong')?.textContent?.trim();
            const budgetText = row.querySelector('.budget')?.textContent || '';
            const value = Number(budgetText.replace(/[^0-9.-]/g, ''));
            if (name && Number.isFinite(value)) {
                labels.push(name.length > 16 ? name.slice(0, 16) + '…' : name);
                values.push(value);
            }
        });

        if (!labels.length) {
            labels.push('No projects');
            values.push(0);
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Project budget (USD)',
                    data: values,
                    backgroundColor: '#7563eb',
                    borderRadius: 6,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: item => '$' + Number(item.raw).toLocaleString('en-US')
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#999caf', maxRotation: 0, minRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f1f6' },
                        ticks: {
                            color: '#999caf',
                            callback: value => '$' + Number(value).toLocaleString('en-US')
                        }
                    }
                }
            }
        });
    }
});
