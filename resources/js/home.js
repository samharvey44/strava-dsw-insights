import polyline from "@mapbox/polyline";

document.addEventListener('DOMContentLoaded', () => {
    window.insertMapWithPolyline = (base64EncodedPolylineString, containerId) => {
        const decodedPolylineString = atob(base64EncodedPolylineString);
        const coords = polyline.decode(decodedPolylineString);

        const map = L.map(containerId).setView(coords[0], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        L.polyline(coords, {color: 'red'}).addTo(map);
    };

    window.dispatchEvent(new CustomEvent('leaflet-maps-ready'));
});
