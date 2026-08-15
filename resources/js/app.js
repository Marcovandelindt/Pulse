import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';
import { initCharts } from './modules/charts.js';
import { registerTvComponents } from './modules/tv.js';
import { registerMovieComponents } from './modules/movies.js';

window.Alpine = Alpine;
Alpine.plugin(Collapse);

registerTvComponents();
registerMovieComponents();

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
});
