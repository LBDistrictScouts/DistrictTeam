<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Appointment $appointment
 * @var \Cake\Collection\CollectionInterface|array<string> $roles
 * @var \Cake\Collection\CollectionInterface|array<string> $members
 * @var array<array<string, string>> $memberContactMethods
 * @var array<string, mixed> $roleSelectorData
 */
?>
<?php
$this->Html->css('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', ['block' => true]);
$this->Html->css('appointment-member-select', ['block' => true]);
$this->Html->css('appointment-role-selector', ['block' => true]);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => true]);
$this->Html->script('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['block' => true]);
$this->Html->script('appointment-member-select', ['block' => true]);
$this->Html->script('appointment-role-selector', ['block' => true]);
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
                echo $this->element('Appointments/role_selector', compact('appointment', 'roles', 'roleSelectorData'));
                echo $this->Form->control('member_id', [
                    'options' => $members,
                    'value' => $appointment->member_id,
                    'data-member-search-url' => $this->Url->build('/api/member-search'),
                ]);
                echo $this->Form->control('member_contact_method_id', [
                    'options' => $memberContactMethods,
                    'data-member-contact-methods-url' => $this->Url->build('/api/appointment-contact-methods'),
                ]);
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
