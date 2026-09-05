(() => {
    const group = document.getElementById('group-id');
    const section = document.getElementById('section-id');
    const parent = document.getElementById('team-parent-id');
    const help = document.getElementById('parent-team-help');
    if (!group || !section || !parent) return;
    const options = Array.from(parent.options);
    const initialParent = parent.value;
    const defaultHelp = help.textContent;

    function filter(initial = false) {
        const selected = parent.value;
        const matches = option => option.value === '' || (
            option.dataset.groupId === group.value &&
            (option.dataset.sectionId === '' || option.dataset.sectionId === section.value)
        );
        const visible = options.filter(option => matches(option) || (initial && option.value === initialParent));
        parent.replaceChildren(...visible);
        parent.value = visible.some(option => option.value === selected) ? selected : '';
        if (initial && selected && !matches(options.find(option => option.value === selected))) {
            help.textContent = 'Your current parent is retained. Other choices match the selected group and section.';
        } else if (selected && !parent.value) {
            help.textContent = 'The previous parent does not match this group or section. Choose a new parent or leave it blank.';
        } else {
            help.textContent = defaultHelp;
        }
    }

    group.addEventListener('change', () => filter());
    section.addEventListener('change', () => filter());
    filter(true);
})();
