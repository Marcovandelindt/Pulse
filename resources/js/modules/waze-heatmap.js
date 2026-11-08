import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.heat';

const container = document.getElementById('waze-heatmap');

if (container) {
    const dataUrl = container.dataset.url;

    const map = L.map(container, { zoomControl: true }).setView([52.3, 4.9], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    fetch(dataUrl)
        .then(r => r.json())
        .then(points => {
            L.heatLayer(points, {
                radius: 12,
                blur: 10,
                maxZoom: 14,
                gradient: { 0.2: '#3b82f6', 0.5: '#8b5cf6', 0.8: '#ec4899', 1.0: '#f97316' },
            }).addTo(map);
        });
}
