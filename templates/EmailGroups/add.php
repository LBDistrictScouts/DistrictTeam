<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\EmailGroup $emailGroup */
/** @var array<string, mixed> $teamSelectorData */
?>
<div class="workspace-page workspace-form-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Back to email groups'), ['action' => 'index']) ?>
    </nav>
    <?= $this->element('Workspace/index_header', [
        'title' => __('Add email group'),
        'description' => __('Give the group a name, address and organisational scope.'),
        'actions' => [],
    ]) ?>
    <div class="workspace-form-panel">
        <?= $this->Form->create($emailGroup) ?>
        <fieldset>
            <legend><?= __('Email group details') ?></legend>
            <?php
                echo $this->Form->control('email_group_name', ['label' => __('Name')]);
                echo $this->Form->control('email_address', ['label' => __('Email address')]);
                echo $this->element('Teams/selector', [
                    'teamSelectorData' => $teamSelectorData,
                    'selectedTeamId' => $emailGroup->team_id,
                    'selectedGroupId' => $emailGroup->group_id,
                    'selectedSectionId' => $emailGroup->section_id,
                    'deriveSectionFromTeam' => false,
                    'disableEmptyControls' => false,
                    'fieldPrefix' => '',
                    'teamRequired' => false,
                    'teamEmpty' => __('No team (group-wide or section-wide)'),
                ]);
                ?>
        </fieldset>
        <div class="workspace-form-footer">
            <?= $this->Form->button(__('Create email group')) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button button-outline']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
<?= $this->Html->css('team-selector') ?>
<?= $this->Html->script('team-selector') ?>
