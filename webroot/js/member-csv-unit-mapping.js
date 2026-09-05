(() => {
    const table = document.getElementById('unit-mapping-table');
    if (!table) return;

    const sectionGroups = JSON.parse(table.dataset.sectionGroups || '{}');
    table.querySelectorAll('tr').forEach(row => {
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
        };

        group.addEventListener('change', updateSections);
        updateSections();
    });
})();
