import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';
import { initCharts } from './modules/charts.js';
import { registerTvComponents } from './modules/tv.js';
import { registerMovieComponents } from './modules/movies.js';
import { registerRecommendationComponents } from './modules/recommendations.js';

window.Alpine = Alpine;
Alpine.plugin(Collapse);

registerTvComponents();
registerMovieComponents();
registerRecommendationComponents();

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
});
