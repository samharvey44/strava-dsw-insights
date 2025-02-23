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

const extractFilterAndSortFromQueryParams = () => {
    const urlParams = new URLSearchParams(window.location.search);

    const filters = urlParams.entries().reduce((acc, [key, value]) => {
        // Extract a filter from the query string.
        // E.g. filters[foo]=bar => { foo: bar }
        const filterMatch = key.match(/^filters\[(.+)\]$/);

        if (filterMatch) {
            acc[filterMatch[1]] = value;
        }

        return acc;
    }, {});
    const sortColumn = urlParams.get('sort');
    const sortDirection = urlParams.get('sort_direction');

    document.querySelectorAll('#activity_filters_modal input[type="checkbox"][id^="filter_"]').forEach((element) => {
        // If this is a checkbox, and we do not have a specific filter set for it, then check it.
        const filterMatchingCheckbox = filters[element.id.replace('filter_', '')];

        element.checked = filterMatchingCheckbox !== undefined
            ? parseInt(filterMatchingCheckbox) === 1
            : true;
    });

    if (sortColumn && sortDirection) {
        const sortButton = document.querySelector(`#sort_${sortColumn}`);

        sortButton?.classList.remove('btn-light');
        sortButton?.classList.add('btn-primary');

        sortButton?.querySelector(`.sort-${sortDirection}`)?.classList.remove('d-none');
    }
}

const addSortingListener = () => {
    document.querySelectorAll('#activity_filters_modal button[id^="sort_"]').forEach((element) => {
        element.addEventListener('click', () => {
            // First set to ASC, then to DESC, then to none.
            const sortAscArrow = element.querySelector('.sort-asc');
            const sortDescArrow = element.querySelector('.sort-desc');

            let sortDirection = null;

            switch (true) {
                case sortAscArrow.classList.contains('d-none') && sortDescArrow.classList.contains('d-none'):
                    element.classList.remove('btn-light');
                    element.classList.add('btn-primary');

                    sortAscArrow.classList.remove('d-none');
                    sortDirection = 'asc';
                    break;

                case sortDescArrow.classList.contains('d-none'):
                    element.classList.remove('btn-light');
                    element.classList.add('btn-primary');

                    sortAscArrow.classList.add('d-none');
                    sortDescArrow.classList.remove('d-none');
                    sortDirection = 'desc';
                    break;

                default:
                    element.classList.remove('btn-primary');
                    element.classList.add('btn-light');

                    sortAscArrow.classList.add('d-none');
                    sortDescArrow.classList.add('d-none');
                    sortDirection = null;
                    break;
            }
        });
    });
}

const addApplyFiltersListener = () => {
    document.getElementById('apply_dsw_filters').addEventListener('click', () => {
        const filterParams = new URLSearchParams();

        document.querySelectorAll('#activity_filters_modal input[type="checkbox"][id^="filter_"]').forEach((element) => {
            filterParams.append(`filters[${element.id.replace('filter_', '')}]`, element.checked ? '1' : '0');
        });

        let activeSortFound = false;

        document.querySelectorAll('#activity_filters_modal button[id^="sort_"]').forEach((element) => {
            if (element.classList.contains('btn-primary') && !activeSortFound) {
                const sortColumn = element.id.replace('sort_', '');
                const sortDirection = element.querySelector('.sort-asc').classList.contains('d-none') ? 'desc' : 'asc';

                filterParams.append('sort', sortColumn);
                filterParams.append('sort_direction', sortDirection);

                activeSortFound = true;
            }
        });

        filterParams.append('page', '1');

        window.location.search = filterParams.toString();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    insertPolylineMaps();
    extractFilterAndSortFromQueryParams();
    addSortingListener();
    addApplyFiltersListener();
});
