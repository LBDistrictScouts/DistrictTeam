(() => {
    const form = document.getElementById('role-mapping-form');
    if (!form) return;
    const units = Array.from(form.querySelectorAll('input[name="units[]"]'));
    const rows = Array.from(form.querySelectorAll('tr[data-unit]'));
    const summary = document.getElementById('mapping-unit-summary');
    const unmappedRows = document.getElementById('unmapped-mapping-rows');
    const mappedRows = document.getElementById('mapped-mapping-rows');
    const mappedCount = document.getElementById('mapped-count');
    const update = () => {
        const selected = new Set(units.filter(unit => unit.checked).map(unit => unit.value));
        let visible = 0;
        let unmapped = 0;
        rows.forEach(row => {
            const mapping = row.querySelector('select').value;
            const mapped = mapping !== '';
            const state = mapping === 'skip' ? 'skipped' : (mapped ? 'mapped' : 'unmapped');
            row.dataset.mappingState = state;
            row.classList.remove('workspace-unmapped-row', 'workspace-skipped-row');
            if (state === 'unmapped') row.classList.add('workspace-unmapped-row');
            if (state === 'skipped') row.classList.add('workspace-skipped-row');
            row.querySelector('.workspace-skipped-status').hidden = mapping !== 'skip';
            (mapped ? mappedRows : unmappedRows).append(row);
            row.hidden = !selected.has(row.dataset.unit);
            if (!row.hidden) {
                visible++;
                if (!mapping) unmapped++;
            }
        });
        mappedCount.textContent = mappedRows.rows.length;
        summary.textContent = `${selected.size} units selected. ${visible} source roles included; ${unmapped} unmapped (members and contacts only).`;
    };
    form.addEventListener('change', update);
    form.querySelectorAll('[data-unit-selection]').forEach(button => {
        button.addEventListener('click', () => {
            const selected = button.dataset.unitSelection === 'all';
            const categoryUnits = units.filter(unit => unit.dataset.unitCategory === button.dataset.unitCategory);
            categoryUnits.forEach(unit => {
                unit.checked = selected;
            });
            update();
        });
    });
    update();
})();
