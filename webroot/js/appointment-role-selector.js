(() => {
    function replaceOptions(select, placeholder, options, selectedValue) {
        const placeholderOption = new Option(placeholder, '');
        const optionElements = options.map(option => new Option(option.text, option.id));
        select.replaceChildren(placeholderOption, ...optionElements);
        select.value = options.some(option => option.id === selectedValue) ? selectedValue : '';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const selector = document.querySelector('[data-role-selector]');
        const dataElement = document.querySelector('[data-role-selector-data]');
        if (!selector || !dataElement) return;

        const data = JSON.parse(dataElement.textContent);
        const teamSelector = selector.querySelector('[data-team-selector]');
        const team = document.getElementById('role-team-id');
        const role = document.getElementById('role-id');
        if (!teamSelector || !team || !role) return;

        const initialRoleId = role.value;

        function updateRoles(selectedRoleId = '') {
            const roles = data.roles.filter(item => item.teamId === team.value);
            replaceOptions(role, 'Choose a role', roles, selectedRoleId);
            role.disabled = roles.length === 0;
        }

        teamSelector.addEventListener('teamselector:changed', () => updateRoles());
        team.addEventListener('change', () => updateRoles());
        queueMicrotask(() => updateRoles(initialRoleId));
    });
})();
