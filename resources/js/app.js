import Alpine from 'alpinejs';
import { initCharts } from './modules/charts.js';
import { registerTvComponents } from './modules/tv.js';
import { registerMovieComponents } from './modules/movies.js';

window.Alpine = Alpine;

registerTvComponents();
registerMovieComponents();

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
});
