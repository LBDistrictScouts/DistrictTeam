(() => {
    const bindToggle = (selector, checkboxSelector, activeLabel, inactiveLabel) => {
        document.querySelectorAll(selector).forEach(button => {
            const group = button.closest('.standard-template-group');
            const checkboxes = () => Array.from(group.querySelectorAll(checkboxSelector));
            const updateLabel = () => {
                const active = checkboxes().every(checkbox => checkbox.checked);
                button.textContent = active ? button.dataset[inactiveLabel] : button.dataset[activeLabel];
            };
            button.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                const active = !checkboxes().every(checkbox => checkbox.checked);
                checkboxes().forEach(checkbox => { checkbox.checked = active; });
                updateLabel();
            });
            checkboxes().forEach(checkbox => checkbox.addEventListener('change', updateLabel));
            updateLabel();
        });
    };

    bindToggle('[data-template-skip-toggle]', 'input[data-template-skip]', 'skipAllLabel', 'unskipAllLabel');
    bindToggle('[data-template-apply-toggle]', 'input[name$="[apply]"]', 'applyAllLabel', 'unapplyAllLabel');
})();
