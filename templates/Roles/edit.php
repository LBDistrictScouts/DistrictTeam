<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 * @var string[]|\Cake\Collection\CollectionInterface $teams
 */
?>
<div class="workspace-page workspace-form-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Back to roles'), ['action' => 'index']) ?>
    </nav>
    <?= $this->element('Workspace/index_header', [
        'title' => __('Edit role'),
        'description' => __('Define a responsibility and the team it belongs to.'),
        'actions' => [],
    ]) ?>
    <div class="workspace-form-panel">
        <?= $this->Form->create($role) ?>
        <fieldset>
            <legend><?= __('Role details') ?></legend>
            <?php
                echo $this->Form->control('team_id', ['options' => $teams]);
                echo $this->Form->control('name');
                echo $this->Form->control('slug');
                echo $this->Form->control('description');
                echo $this->Form->control('is_lead', [
                    'type' => 'checkbox',
                    'label' => __('Team lead role'),
                ]);
                echo $this->Form->control('multi_member_role', [
                    'type' => 'checkbox',
                    'label' => __('Multi Member Role'),
                    'help' => __('Keep this role open for further appointments.'),
                ]);
            ?>
        </fieldset>
        <div class="workspace-form-footer">
            <?= $this->Form->button(__('Save changes')) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'view', $role->id], ['class' => 'button button-outline']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
