document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-contact-method-modal]').forEach(dialog => {
        const member = dialog.dataset.memberSelectId ? document.getElementById(dialog.dataset.memberSelectId) : null;
        const status = dialog.querySelector('[role="status"]');
        const form = dialog.querySelector('[data-contact-method-form]');
        document.querySelectorAll(`[data-open-contact-method-modal="${dialog.id}"]`).forEach(open => {
            open.addEventListener('click', () => {
                status.textContent = member && !member.value ? 'Choose a member before adding a contact method.' : '';
                dialog.showModal();
            });
        });
        dialog.querySelector('[data-close-contact-method-modal]').addEventListener('click', () => dialog.close());
        form.addEventListener('contactmethod:created', () => dialog.close());
    });
});
