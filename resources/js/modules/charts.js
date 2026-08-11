export function initCharts() {
    document.querySelectorAll('[data-chart]').forEach(el => {
        const type = el.dataset.chart;
        const data = JSON.parse(el.dataset.chartData ?? '{}');
        renderChart(el, type, data);
    });
}

function renderChart(el, type, data) {
    // Chart rendering logic — implement when chart library is chosen
}
