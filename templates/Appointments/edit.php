<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Appointment $appointment
 * @var string[]|\Cake\Collection\CollectionInterface $roles
 * @var string[]|\Cake\Collection\CollectionInterface $members
 * @var string[]|\Cake\Collection\CollectionInterface $memberContactMethods
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
        <?= $this->Form->create($appointment) ?>
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
