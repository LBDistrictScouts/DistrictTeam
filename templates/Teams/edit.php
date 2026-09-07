<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 * @var string[]|\Cake\Collection\CollectionInterface $parentTeam
 */
?>
<div class="workspace-page workspace-form-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Back to teams'), ['action' => 'index']) ?>
    </nav>
    <?= $this->element('Workspace/index_header', [
        'title' => __('Edit team'),
        'description' => __('Shape the team and connect it to its group and section.'),
        'actions' => [],
    ]) ?>
    <div class="workspace-form-panel">
        <?= $this->Form->create($team) ?>
        <fieldset>
            <legend><?= __('Team details') ?></legend>
            <?php
                echo $this->Form->control('team_parent_id', [
                    'options' => $parentTeam,
                    'empty' => __('No parent team'),
                    'aria-describedby' => 'parent-team-help',
                ]);
                echo $this->Form->control('team_name');
                echo $this->Form->control('group_id', ['options' => $groups, 'empty' => __('Choose a group'), 'required' => true]);
                echo $this->Form->control('section_id', [
                    'options' => $sections,
                    'empty' => 'No section (district or group team)',
                ]);
                echo $this->Form->control('template', [
                    'options' => \App\Model\Enum\TeamTemplate::options(),
                    'empty' => __('No standard template'),
                ]);
            ?>
            <p id="parent-team-help" class="team-parent-help" role="status" aria-live="polite"><?= __('Parents match the selected group. Section teams can also belong to a group-level parent.') ?></p>
        </fieldset>
        <div class="workspace-form-footer">
            <?= $this->Form->button(__('Save changes')) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'view', $team->id], ['class' => 'button button-outline']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>

<?= $this->Html->script('team-parent-filter') ?>
