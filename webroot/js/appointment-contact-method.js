document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('contact-method-modal');
    const open = document.getElementById('open-contact-method-modal');
    const close = document.getElementById('close-contact-method-modal');
    const form = document.getElementById('add-appointment-contact-method-form');
    const status = document.getElementById('appointment-contact-method-status');
    const member = document.getElementById('member-id');
    if (!dialog || !open || !close || !form || !status || !member) return;
    open.addEventListener('click', () => {
        if (!member.value) {
            status.textContent = 'Choose a member before adding a contact method.';
        }
        dialog.showModal();
    });
    close.addEventListener('click', () => dialog.close());
    form.addEventListener('contactmethod:created', () => dialog.close());
});
