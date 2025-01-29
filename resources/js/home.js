import polyline from "@mapbox/polyline";

const insertPolylineMaps = () => {
    document.querySelectorAll('div[data-map-polyline]').forEach((element) => {
        const base64EncodedPolylineString = element.getAttribute('data-map-polyline');
        const containerId = element.getAttribute('id');

        const decodedPolylineString = atob(base64EncodedPolylineString);
        const coords = polyline.decode(decodedPolylineString);

        const map = L.map(containerId).setView(coords[0], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        L.polyline(coords, {color: '#FC4C02'}).addTo(map);
    });
}

const applyFilterAndSortFromQueryParams = () => {
    const urlParams = new URLSearchParams(window.location.search);

    const filters = urlParams.entries().reduce((acc, [key, value]) => {
        const filterMatch = key.match(/^filters\[(.+)\]$/);

        if (filterMatch) {
            acc[filterMatch[1]] = value;
        }

        return acc;
    }, {});
    const sort = urlParams.get('sort');

    document.querySelectorAll('#activity_filters_modal input[type="checkbox"][id^="filter_"]').forEach((element) => {
        // If this is a checkbox, and we do not have a specific filter set for it, then check it.
        const filterMatchingCheckbox = filters[element.id.replace('filter_', '')];

        element.checked = filterMatchingCheckbox !== undefined
            ? parseInt(filterMatchingCheckbox) === 1
            : true;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    insertPolylineMaps();
    applyFilterAndSortFromQueryParams();
});
