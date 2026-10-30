(() => {
    document.addEventListener('DOMContentLoaded', () => {
        const picker = document.querySelector('[data-tag-assignment-picker]');
        if (!picker) return;

        const typeSelect = picker.querySelector('[data-assignment-type]');
        const rows = picker.querySelector('[data-tag-assignment-rows]');
        const emptyRow = rows.querySelector('[data-assignment-empty]');
        const dialogs = new Map(Array.from(document.querySelectorAll('[data-assignment-modal]'))
            .map(dialog => [dialog.dataset.assignmentModal, dialog]));
        const typeLabels = { team: 'Team', role: 'Role', appointment: 'Appointment' };

        function updateEmptyState() {
            emptyRow.hidden = rows.querySelectorAll('[data-assignment-row]').length > 0;
        }

        function addAssignment(type, id, text) {
            const duplicate = Array.from(rows.querySelectorAll('[data-assignment-row]'))
                .some(row => row.dataset.type === type && row.dataset.id === id);
            const status = document.querySelector(`[data-assignment-status="${type}"]`);
            if (duplicate) {
                if (status) status.textContent = `${typeLabels[type]} is already assigned to this tag.`;
                return false;
            }

            const row = document.createElement('tr');
            row.dataset.assignmentRow = '';
            row.dataset.type = type;
            row.dataset.id = id;

            const typeCell = document.createElement('td');
            typeCell.textContent = typeLabels[type];
            const textCell = document.createElement('td');
            textCell.textContent = text;
            const actionCell = document.createElement('td');
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = `${type}s[_ids][]`;
            hidden.value = id;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'button button-clear';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', `Remove ${typeLabels[type]}: ${text}`);
            remove.dataset.removeAssignment = '';
            actionCell.append(hidden, remove);
            row.append(typeCell, textCell, actionCell);
            rows.insertBefore(row, emptyRow);
            updateEmptyState();

            return true;
        }

        function closeDialog(dialog) {
            if (dialog?.open) dialog.close();
        }

        picker.addEventListener('click', event => {
            const remove = event.target.closest('[data-remove-assignment]');
            if (!remove) return;
            remove.closest('[data-assignment-row]').remove();
            updateEmptyState();
        });

        picker.querySelector('[data-open-assignment-modal]').addEventListener('click', () => {
            const type = typeSelect.value;
            const dialog = dialogs.get(type);
            if (!dialog) {
                typeSelect.focus();
                return;
            }
            const status = dialog.querySelector(`[data-assignment-status="${type}"]`);
            if (status) status.textContent = type === 'role' ? '' : 'Enter at least one character to search.';
            const search = dialog.querySelector(`[data-assignment-search="${type}"]`);
            if (search) {
                search.value = '';
                dialog.querySelector(`[data-assignment-results="${type}"]`).replaceChildren();
            }
            dialog.showModal();
            (search || dialog.querySelector('#role-team-id'))?.focus();
        });

        document.querySelectorAll('[data-close-assignment-modal]').forEach(button => {
            button.addEventListener('click', () => closeDialog(button.closest('dialog')));
        });

        document.querySelectorAll('[data-assignment-search]').forEach(input => {
            let timer;
            let requestNumber = 0;
            input.addEventListener('input', () => {
                window.clearTimeout(timer);
                requestNumber += 1;
                const currentRequest = requestNumber;
                const type = input.dataset.assignmentSearch;
                const dialog = dialogs.get(type);
                const results = dialog.querySelector(`[data-assignment-results="${type}"]`);
                const status = dialog.querySelector(`[data-assignment-status="${type}"]`);
                const term = input.value.trim();
                results.replaceChildren();
                if (term.length === 0) {
                    status.textContent = 'Enter at least one character to search.';
                    return;
                }
                status.textContent = 'Searching…';
                timer = window.setTimeout(async () => {
                    const url = new URL(picker.dataset.searchUrl, window.location.href);
                    url.searchParams.set('type', type);
                    url.searchParams.set('q', term);
                    try {
                        const response = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error('Search failed. Try again.');
                        const payload = await response.json();
                        if (currentRequest !== requestNumber) return;
                        results.replaceChildren();
                        for (const result of payload.results || []) {
                            const item = document.createElement('li');
                            const select = document.createElement('button');
                            select.type = 'button';
                            select.className = 'tag-assignment-result';
                            select.textContent = result.text;
                            select.addEventListener('click', () => {
                                if (addAssignment(type, result.id, result.text)) closeDialog(dialog);
                            });
                            item.append(select);
                            results.append(item);
                        }
                        status.textContent = results.childElementCount ? 'Select a result.' : 'No matches found.';
                    } catch (error) {
                        if (currentRequest !== requestNumber) return;
                        status.textContent = error instanceof Error ? error.message : 'Search failed. Try again.';
                    }
                }, 250);
            });
        });

        const roleDialog = dialogs.get('role');
        roleDialog.querySelector('[data-add-selected-role]').addEventListener('click', () => {
            const team = roleDialog.querySelector('#role-team-id');
            const role = roleDialog.querySelector('#role-id');
            const status = roleDialog.querySelector('[data-assignment-status="role"]');
            if (!team.value || !role.value) {
                status.textContent = 'Choose a team and role.';
                return;
            }
            const teamName = team.selectedOptions[0]?.textContent || '';
            const roleName = role.selectedOptions[0]?.textContent || '';
            const assignmentName = roleName.includes(' · ') ? roleName : `${roleName} · ${teamName}`;
            if (addAssignment('role', role.value, assignmentName)) {
                role.value = '';
                closeDialog(roleDialog);
            }
        });

        updateEmptyState();
    });
})();
