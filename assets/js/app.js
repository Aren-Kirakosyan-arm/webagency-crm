document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('projectModal');
    const openButtons = ['newProjectBtn', 'newProjectBtn2', 'emptyNewProjectBtn']
        .map(id => document.getElementById(id)).filter(Boolean);
    const closeButtons = ['closeModal', 'cancelModal']
        .map(id => document.getElementById(id)).filter(Boolean);
    const openModal = () => {
        if (!modal) return;
        modal.classList.add('visible');
        modal.setAttribute('aria-hidden', 'false');
        const firstInput = modal.querySelector('input[name="project_name"]');
        if (firstInput) firstInput.focus();
    };
    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('visible');
        modal.setAttribute('aria-hidden', 'true');
    };
    openButtons.forEach(button => button.addEventListener('click', openModal));
    closeButtons.forEach(button => button.addEventListener('click', closeModal));
    if (modal) modal.addEventListener('click', event => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeModal();
    });

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('sidebar-open'));
    }

    const canvas = document.getElementById('projectsChart');
    if (canvas && window.Chart && window.crmChartData) {
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Планирование', 'В работе', 'Завершено'],
                datasets: [{
                    data: [
                        window.crmChartData.planning,
                        window.crmChartData.in_progress,
                        window.crmChartData.completed
                    ],
                    backgroundColor: ['#a5b4fc', '#6675f5', '#34c99a'],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }
});
