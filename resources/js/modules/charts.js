import Chart from 'chart.js/auto';

const PINK   = '#fa709a';
const YELLOW = '#fee140';
const PURPLE = '#764ba2';
const BLUE   = '#4facfe';
const GRID   = 'rgba(255,255,255,0.06)';
const TICK   = 'rgba(255,255,255,0.5)';

const DEFAULTS = {
    color: TICK,
    font: { family: 'Inter, system-ui, sans-serif', size: 12 },
};

Chart.defaults.color = TICK;
Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

export function initCharts() {
    document.querySelectorAll('[data-chart]').forEach(el => {
        const type = el.dataset.chart;
        const raw  = el.dataset.chartData ?? '{}';
        try {
            renderChart(el, type, JSON.parse(raw));
        } catch (e) {
            console.warn('Chart init failed', e);
        }
    });
}

function renderChart(el, type, data) {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
        },
        scales: {
            x: {
                grid: { color: GRID },
                ticks: { color: TICK },
            },
            y: {
                grid: { color: GRID },
                ticks: { color: TICK },
                beginAtZero: true,
            },
        },
    };

    switch (type) {
        case 'bar':
            new Chart(el, {
                type: 'bar',
                data: {
                    labels: data.labels ?? [],
                    datasets: [{
                        data: data.values ?? [],
                        backgroundColor: BLUE,
                        borderRadius: 4,
                    }],
                },
                options: { ...baseOptions, ...data.options },
            });
            break;

        case 'line':
            new Chart(el, {
                type: 'line',
                data: {
                    labels: data.labels ?? [],
                    datasets: [{
                        data: data.values ?? [],
                        borderColor: PURPLE,
                        backgroundColor: 'rgba(118,75,162,0.15)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                    }],
                },
                options: { ...baseOptions, ...data.options },
            });
            break;

        case 'scatter':
            new Chart(el, {
                type: 'scatter',
                data: {
                    datasets: [{
                        data: data.points ?? [],
                        backgroundColor: PINK,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }],
                },
                options: {
                    ...baseOptions,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (ctx) => data.labels?.[ctx.dataIndex] ?? `(${ctx.parsed.x}, ${ctx.parsed.y})`,
                            },
                        },
                    },
                },
            });
            break;
    }
}
