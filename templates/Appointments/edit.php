<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Appointment $appointment
 * @var \Cake\Collection\CollectionInterface|array<string> $roles
 * @var \Cake\Collection\CollectionInterface|array<string> $members
 * @var array<array<string, string>> $memberContactMethods
 */
?>
<div class="workspace-page workspace-form-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Back to appointments'), ['action' => 'index']) ?>
    </nav>
    <?= $this->element('Workspace/index_header', [
        'title' => __('Edit appointment'),
        'description' => __('Connect a member to a role and set their appointment dates.'),
        'actions' => [],
    ]) ?>
    <div class="workspace-form-panel">
        <?= $this->Form->create($appointment, ['id' => 'appointment-form']) ?>
        <fieldset>
            <legend><?= __('Appointment details') ?></legend>
            <?php
                echo $this->Form->control('role_id', ['options' => $roles]);
                echo $this->Form->control('member_id', ['options' => $members]);
                echo $this->Form->control('member_contact_method_id', ['options' => $memberContactMethods]);
                echo $this->Form->control('effective_start_date');
                echo $this->Form->control('effective_end_date', ['empty' => true]);
            ?>
        </fieldset>
        <div class="workspace-form-footer">
            <?= $this->Form->button(__('Save changes')) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'view', $appointment->id], ['class' => 'button button-outline']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
document.addEventListener('DOMContentLoaded', function () {
    const memberSelect = document.getElementById('member-id');
    const contactMethodSelect = document.getElementById('member-contact-method-id');
    const contactMethods = Array.from(contactMethodSelect.options);

    const updateContactMethods = function () {
        const selectedId = contactMethodSelect.value;
        const options = contactMethods.filter(function (option) {
            return option.dataset.memberId === memberSelect.value;
        });
        contactMethodSelect.replaceChildren(...options);
        if (options.some(option => option.value === selectedId)) {
            contactMethodSelect.value = selectedId;
        }
        if (options.length === 0) {
            contactMethodSelect.add(new Option('<?= h(__('No contact methods available')) ?>', ''));
        }
    };

    memberSelect.addEventListener('change', updateContactMethods);
    updateContactMethods();
});
<?php $this->Html->scriptEnd(); ?>
