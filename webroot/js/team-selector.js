(() => {
    function replaceOptions(select, placeholder, options, selectedValue) {
        select.replaceChildren(new Option(placeholder, ''), ...options.map(option => new Option(option.text, option.id)));
        select.value = options.some(option => option.id === selectedValue) ? selectedValue : '';
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-team-selector]').forEach(selector => {
            const dataElement = selector.querySelector('[data-team-selector-data]');
            const group = selector.querySelector('select[name$="group_id"]');
            const section = selector.querySelector('select[name$="section_id"]');
            const team = document.getElementById(selector.dataset.teamField.replaceAll('_', '-'));
            if (!dataElement || !group || !section || !team) return;

            const data = JSON.parse(dataElement.textContent);
            const selectedTeamId = selector.dataset.selectedTeamId || team.value;
            const initialTeam = data.teams.find(item => item.id === selectedTeamId);

            function notify() {
                selector.dispatchEvent(new CustomEvent('teamselector:changed', { detail: { teamId: team.value } }));
            }
            function updateTeams(selectedId = '') {
                const teams = data.teams.filter(item => item.groupId === group.value && (section.value === '' || item.sectionId === section.value));
                replaceOptions(team, 'Choose a team', teams, selectedId);
                team.disabled = teams.length === 0;
                notify();
            }
            function updateSections(selectedId = '', selectedTeamId = '') {
                const sections = data.sections.filter(item => item.groupId === group.value);
                replaceOptions(section, 'Any section', sections, selectedId);
                section.disabled = sections.length === 0;
                updateTeams(selectedTeamId);
            }

            group.addEventListener('change', () => updateSections());
            section.addEventListener('change', () => updateTeams());
            team.addEventListener('change', notify);

            replaceOptions(group, 'Choose a group', data.groups, initialTeam ? initialTeam.groupId : '');
            updateSections(initialTeam ? initialTeam.sectionId : '', initialTeam ? initialTeam.id : '');
        });
    });
})();
