import Alpine from 'alpinejs';
import { initCharts } from './modules/charts.js';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
});
