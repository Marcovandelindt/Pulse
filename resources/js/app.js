import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';
import { initCharts } from './modules/charts.js';
import { registerTvComponents } from './modules/tv.js';
import { registerMovieComponents } from './modules/movies.js';
import { registerRecommendationComponents } from './modules/recommendations.js';
import { registerPlayStationComponents } from './modules/playstation.js';
import { registerHealthComponents } from './modules/health.js';
import { registerCalendarComponents } from './modules/calendar.js';
import { registerGlobalSearch } from './modules/globalSearch.js';
import { initQuillEditors } from './modules/quill-editor.js';

window.Alpine = Alpine;
Alpine.plugin(Collapse);

registerTvComponents();
registerMovieComponents();
registerRecommendationComponents();
registerPlayStationComponents();
registerHealthComponents();
registerCalendarComponents();
registerGlobalSearch();

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    initQuillEditors();
});
