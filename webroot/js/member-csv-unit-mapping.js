(() => {
    const table = document.getElementById('unit-mapping-table');
    if (!table) return;

    const sectionGroups = JSON.parse(table.dataset.sectionGroups || '{}');
    const unmappedRows = document.getElementById('unit-unmapped-mapping-rows');
    const mappedRows = document.getElementById('unit-mapped-mapping-rows');
    const mappedCount = document.getElementById('unit-mapped-count');
    const updateMappedCount = () => {
        mappedCount.textContent = mappedRows.rows.length;
    };
    table.querySelectorAll('tbody tr').forEach(row => {
        const group = row.querySelector('.unit-group-select');
        const section = row.querySelector('.unit-section-select');
        if (!group || !section) return;

        const updateSections = () => {
            const groupId = group.value;
            section.disabled = groupId === '';
            Array.from(section.options).forEach(option => {
                const available = option.value === '' || sectionGroups[option.value] === groupId;
                option.hidden = !available;
                option.disabled = !available;
            });
            if (section.value && sectionGroups[section.value] !== groupId) {
                section.value = '';
            }
            const complete = groupId !== '' && (row.dataset.requiresSection !== 'true' || section.value !== '');
            row.classList.toggle('workspace-unmapped-row', !complete);
            (complete ? mappedRows : unmappedRows).append(row);
            updateMappedCount();
        };

        group.addEventListener('change', updateSections);
        section.addEventListener('change', updateSections);
        updateSections();
    });
})();
