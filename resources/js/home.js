import polyline from "@mapbox/polyline";

const insertMapWithPolyline = (base64EncodedPolylineString, containerId) => {
    const decodedPolylineString = atob(base64EncodedPolylineString);
    const coords = polyline.decode(decodedPolylineString);

    const map = L.map(containerId).setView(coords[0], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    L.polyline(coords, {color: '#FC4C02'}).addTo(map);
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('div[data-map-polyline]').forEach((element) => {
        const base64EncodedPolylineString = element.getAttribute('data-map-polyline');
        const containerId = element.getAttribute('id');

        insertMapWithPolyline(base64EncodedPolylineString, containerId);
    });
});
