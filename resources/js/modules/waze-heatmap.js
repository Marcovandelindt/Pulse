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
        .then(routes => {
            const routeLayer = L.layerGroup();
            const allPoints = [];

            routes.forEach(points => {
                L.polyline(points, { color: '#6366f1', weight: 2, opacity: 0.55 }).addTo(routeLayer);
                points.forEach(p => allPoints.push(p));
            });

            const heatLayer = L.heatLayer(allPoints, {
                radius: 12,
                blur: 10,
                maxZoom: 14,
                gradient: { 0.2: '#3b82f6', 0.5: '#8b5cf6', 0.8: '#ec4899', 1.0: '#f97316' },
            });

            routeLayer.addTo(map);

            L.control.layers(
                {},
                { 'Routes': routeLayer, 'Heatmap': heatLayer },
                { collapsed: false, position: 'topright' }
            ).addTo(map);

            if (allPoints.length > 0) {
                map.fitBounds(L.latLngBounds(allPoints), { padding: [32, 32] });
            }
        });
}
