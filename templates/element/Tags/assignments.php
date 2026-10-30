<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $roles
 * @var \App\Model\Entity\Appointment $roleSelection
 * @var array<string, mixed> $teamSelectorData
 * @var array<string, mixed> $roleSelectorData
 * @var list<array{type: string, id: string, text: string}> $assignmentRows
 */
$this->Html->css('team-selector', ['block' => true]);
$this->Html->script('team-selector', ['block' => true]);
$this->Html->script('appointment-role-selector', ['block' => true]);
$this->Html->script('tags-assignment-picker', ['block' => true]);
$typeLabels = [
    'team' => __('Team'),
    'role' => __('Role'),
    'appointment' => __('Appointment'),
];
$appointment = $roleSelection;
$groupRequired = false;
$teamRequired = false;
$roleRequired = false;
?>
<fieldset class="tag-assignments" data-tag-assignment-picker
    data-search-url="<?= h($this->Url->build(['controller' => 'Tags', 'action' => 'assignmentOptions'])) ?>">
    <legend><?= __('Assignments') ?></legend>
    <p><?= __('Add teams, roles or appointments that use this tag. You can assign the tag at more than one level.') ?></p>
    <div class="tag-assignment-add-row">
        <label for="tag-assignment-type"><?= __('Add an assignment') ?></label>
        <select id="tag-assignment-type" data-assignment-type>
            <option value=""><?= __('Choose a type') ?></option>
            <option value="team"><?= __('Team') ?></option>
            <option value="role"><?= __('Role') ?></option>
            <option value="appointment"><?= __('Appointment') ?></option>
        </select>
        <button type="button" class="button button-outline" data-open-assignment-modal><?= __('Add') ?></button>
    </div>
    <div class="table-responsive">
        <input type="hidden" name="teams[_ids]" value="">
        <input type="hidden" name="roles[_ids]" value="">
        <input type="hidden" name="appointments[_ids]" value="">
        <table class="tag-assignment-table">
            <thead><tr><th><?= __('Type') ?></th><th><?= __('Assignment') ?></th><th><?= __('Actions') ?></th></tr></thead>
            <tbody data-tag-assignment-rows>
                <?php foreach ($assignmentRows as $assignment) : ?>
                <tr data-assignment-row data-type="<?= h($assignment['type']) ?>" data-id="<?= h($assignment['id']) ?>">
                    <td><?= h($typeLabels[$assignment['type']] ?? $assignment['type']) ?></td>
                    <td><?= h($assignment['text']) ?></td>
                    <td>
                        <input type="hidden" name="<?= h($assignment['type']) ?>s[_ids][]" value="<?= h($assignment['id']) ?>">
                        <button type="button" class="button button-clear" data-remove-assignment><?= __('Remove') ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr data-assignment-empty<?= $assignmentRows ? ' hidden' : '' ?>><td colspan="3" class="workspace-empty"><?= __('No assignments added yet.') ?></td></tr>
            </tbody>
        </table>
    </div>
</fieldset>

<dialog class="tag-assignment-modal" data-assignment-modal="team">
    <h2><?= __('Select a team') ?></h2>
    <label for="team-assignment-search"><?= __('Search teams') ?></label>
    <input id="team-assignment-search" type="search" data-assignment-search="team" autocomplete="off">
    <p data-assignment-status="team" role="status" aria-live="polite"><?= __('Enter at least one character to search.') ?></p>
    <ul class="tag-assignment-results" data-assignment-results="team"></ul>
    <button type="button" class="button button-outline" data-close-assignment-modal><?= __('Cancel') ?></button>
</dialog>

<dialog class="tag-assignment-modal" data-assignment-modal="role">
    <h2><?= __('Select a role') ?></h2>
    <?= $this->element('Appointments/role_selector', compact(
        'appointment',
        'roles',
        'teamSelectorData',
        'roleSelectorData',
        'groupRequired',
        'teamRequired',
        'roleRequired',
    )) ?>
    <p data-assignment-status="role" role="status" aria-live="polite"></p>
    <div class="tag-assignment-modal-actions">
        <button type="button" class="button" data-add-selected-role><?= __('Add role') ?></button>
        <button type="button" class="button button-outline" data-close-assignment-modal><?= __('Cancel') ?></button>
    </div>
</dialog>

<dialog class="tag-assignment-modal" data-assignment-modal="appointment">
    <h2><?= __('Select an appointment') ?></h2>
    <label for="appointment-assignment-search"><?= __('Search members or roles') ?></label>
    <input id="appointment-assignment-search" type="search" data-assignment-search="appointment" autocomplete="off">
    <p data-assignment-status="appointment" role="status" aria-live="polite"><?= __('Enter at least one character to search.') ?></p>
    <ul class="tag-assignment-results" data-assignment-results="appointment"></ul>
    <button type="button" class="button button-outline" data-close-assignment-modal><?= __('Cancel') ?></button>
</dialog>
