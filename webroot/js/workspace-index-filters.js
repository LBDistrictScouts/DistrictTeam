(function () {
    'use strict';

    var controls = document.querySelector('[data-workspace-index-filters]');
    if (!controls) {
        return;
    }

    var storageKey = 'workspace-index-filters-open';
    var hasActiveFilters = controls.dataset.hasActiveFilters === 'true';
    try {
        var cachedState = window.sessionStorage.getItem(storageKey);
        if (hasActiveFilters) {
            controls.open = true;
        } else if (cachedState !== null) {
            controls.open = cachedState === 'true';
        }
        controls.addEventListener('toggle', function () {
            window.sessionStorage.setItem(storageKey, String(controls.open));
        });
    } catch (error) {
        // The controls remain usable when browser storage is unavailable.
    }
}());
