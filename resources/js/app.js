import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.buildTooltips = () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        const tooltipInstance = bootstrap.Tooltip.getInstance(element);

        if (!tooltipInstance) {
            return;
        }

        tooltipInstance.dispose();
    });
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new bootstrap.Tooltip(element);
    });
}

window.axios.interceptors.response.use(
    (response) => {
        // Ensure tooltips are rebuilt in case DOM has been updated
        setTimeout(() => {
            buildTooltips();
        }, 1000);

        return response;
    },
    (error) => {
        buildTooltips();

        return Promise.reject(error);
    },
);

buildTooltips();
