import Alpine from 'alpinejs';
import { initCharts } from './modules/charts.js';
import { registerTvComponents } from './modules/tv.js';
import { registerMovieComponents } from './modules/movies.js';
import { registerMusicComponents } from './modules/music.js';

window.Alpine = Alpine;

registerTvComponents();
registerMovieComponents();
registerMusicComponents();

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
});
