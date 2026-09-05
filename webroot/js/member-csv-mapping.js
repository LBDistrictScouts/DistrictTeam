(() => {
    const form = document.getElementById('role-mapping-form');
    if (!form) return;
    const units = Array.from(form.querySelectorAll('input[name="units[]"]'));
    const rows = Array.from(form.querySelectorAll('tr[data-unit]'));
    const summary = document.getElementById('mapping-unit-summary');
    const update = () => {
        const selected = new Set(units.filter(unit => unit.checked).map(unit => unit.value));
        let visible = 0;
        let unmapped = 0;
        rows.forEach(row => {
            row.hidden = !selected.has(row.dataset.unit);
            if (!row.hidden) {
                visible++;
                if (!row.querySelector('select').value) unmapped++;
            }
        });
        summary.textContent = `${selected.size} units selected. ${visible} source roles included; ${unmapped} unmapped (members and contacts only).`;
    };
    form.addEventListener('change', update);
    form.querySelectorAll('[data-unit-selection]').forEach(button => {
        button.addEventListener('click', () => {
            const selected = button.dataset.unitSelection === 'all';
            units.forEach(unit => {
                unit.checked = selected;
            });
            update();
        });
    });
    update();
})();
