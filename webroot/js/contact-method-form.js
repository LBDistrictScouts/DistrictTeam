(() => {
    function messages(errors) {
        return Object.values(errors || {}).flatMap(error => typeof error === 'string' ? [error] : messages(error));
    }

    function deleteControl(contactMethod, csrfToken) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = contactMethod.delete_url;
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_csrfToken';
        token.value = csrfToken;
        const button = document.createElement('button');
        button.type = 'submit';
        button.className = 'member-contact-delete';
        button.title = 'Delete contact method';
        button.setAttribute('aria-label', `Delete ${contactMethod.contact_method}`);
        button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16M10 11v6m4-6v6M9 7l1-2h4l1 2m-9 0 1 13h10l1-13"/></svg>';
        form.append(token, button);
        return form;
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-contact-method-form]').forEach(form => {
            const status = document.getElementById(form.dataset.contactMethodStatusId);
            const memberSelect = form.dataset.memberSelectId ? document.getElementById(form.dataset.memberSelectId) : null;
            const contactSelect = form.dataset.contactMethodSelectId ? document.getElementById(form.dataset.contactMethodSelectId) : null;
            form.addEventListener('submit', async event => {
                event.preventDefault();
                const memberId = memberSelect ? memberSelect.value : '';
                if (memberSelect && !memberId) {
                    status.textContent = 'Choose a member before adding a contact method.';
                    return;
                }
                const button = form.querySelector('button[type="submit"]')
                    || document.querySelector(`button[form="${form.id}"][type="submit"]`);
                if (!button) return;
                button.disabled = true;
                status.textContent = 'Saving…';
                try {
                    const url = form.dataset.contactMethodUrlBase
                        ? form.dataset.contactMethodUrlBase + encodeURIComponent(memberId)
                        : form.action;
                    const response = await fetch(url, {
                        method: 'POST', body: new FormData(form),
                        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(messages(result.errors).join(' ') || 'Unable to save the contact method.');
                    if (
                        form.dataset.appointmentEmailOnly === 'true'
                        && (result.contactMethod.is_non_group_email || !result.contactMethod.is_appointment_email)
                    ) {
                        status.textContent = 'Only group email contact methods can be used for an appointment. Add one to continue.';
                        return;
                    }
                    if (contactSelect) {
                        contactSelect.add(new Option(result.contactMethod.contact_method, result.contactMethod.id, true, true));
                        contactSelect.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                    if (form.dataset.contactMethodListId) {
                        const row = document.createElement('li');
                        const type = document.createElement('span');
                        const method = document.createElement('strong');
                        type.className = 'member-contact-type';
                        type.textContent = result.contactMethod.contact_method_type;
                        if (result.contactMethod.is_non_group_email) {
                            row.className = 'member-contact-non-group-email';
                            const warning = document.createElement('span');
                            warning.className = 'member-contact-warning';
                            warning.textContent = 'Non-group email';
                            type.append(warning);
                        }
                        method.textContent = result.contactMethod.contact_method;
                        row.append(type, method, deleteControl(
                            result.contactMethod,
                            form.querySelector('input[name="_csrfToken"]').value,
                        ));
                        let list = document.getElementById(form.dataset.contactMethodListId);
                        if (!list) {
                            list = document.createElement('ul');
                            list.id = form.dataset.contactMethodListId;
                            list.className = 'member-contact-list';
                            document.getElementById(form.dataset.contactMethodEmptyId).replaceWith(list);
                        }
                        list.append(row);
                    }
                    form.reset();
                    status.textContent = 'Contact method added.';
                    form.dispatchEvent(new CustomEvent('contactmethod:created', {detail: result.contactMethod}));
                } catch (error) {
                    status.textContent = error.message;
                } finally {
                    button.disabled = false;
                }
            });
        });
    });
})();
