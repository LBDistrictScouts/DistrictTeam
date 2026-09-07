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
        const group = document.getElementById('role-group-id');
        const section = document.getElementById('role-section-id');
        const team = document.getElementById('role-team-id');
        const role = document.getElementById('role-id');
        if (!group || !section || !team || !role) return;

        const initialRoleId = role.value;
        const initialRole = data.roles.find(item => item.id === initialRoleId);
        const initialTeam = initialRole && data.teams.find(item => item.id === initialRole.teamId);

        function updateRoles(selectedRoleId = '') {
            const roles = data.roles.filter(item => item.teamId === team.value);
            replaceOptions(role, 'Choose a role', roles, selectedRoleId);
            role.disabled = roles.length === 0;
        }

        function updateTeams(selectedTeamId = '') {
            const teams = data.teams.filter(item => item.groupId === group.value && (
                section.value === '' || item.sectionId === section.value
            ));
            replaceOptions(team, 'Choose a team', teams, selectedTeamId);
            team.disabled = teams.length === 0;
            updateRoles();
        }

        function updateSections(selectedSectionId = '') {
            const sections = data.sections.filter(item => item.groupId === group.value);
            replaceOptions(section, 'Any section', sections, selectedSectionId);
            section.disabled = sections.length === 0;
            updateTeams();
        }

        group.addEventListener('change', () => updateSections());
        section.addEventListener('change', () => updateTeams());
        team.addEventListener('change', () => updateRoles());

        replaceOptions(group, 'Choose a group', data.groups, initialTeam ? initialTeam.groupId : '');
        updateSections(initialTeam ? initialTeam.sectionId : '');
        updateTeams(initialTeam ? initialTeam.id : '');
        updateRoles(initialRoleId);
    });
})();
