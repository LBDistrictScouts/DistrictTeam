<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\Tag $tag */
/** @var array<string, string> $roles */
/** @var array<string, string> $emailGroups */
/** @var \App\Model\Entity\Appointment $roleSelection */
/** @var array<string, mixed> $teamSelectorData */
/** @var array<string, mixed> $roleSelectorData */
/** @var list<array{type: string, id: string, text: string}> $assignmentRows */
?>
<div class="workspace-page workspace-form-page">
    <?= $this->element('Workspace/index_header', ['title' => __('Add tag'), 'description' => __('Create a visual label and a stable code for workflows.'), 'actions' => []]) ?>
    <div class="workspace-form-panel">
        <?= $this->Form->create($tag) ?>
        <fieldset><legend><?= __('Tag details') ?></legend>
            <?= $this->Form->control('tag_label', ['label' => __('Tag label'), 'help' => __('This is the visual tag shown throughout the directory.')]) ?>
            <?= $this->Form->control('tag_code', ['label' => __('Tag code'), 'help' => __('Letters, numbers and hyphens only. Workflows can use this stable code.')]) ?>
            <?= $this->Form->control('is_cognito_permissions_group', [
                'type' => 'checkbox',
                'label' => __('Cognito permissions group'),
                'help' => __('Mark this tag as a Cognito permissions group.'),
                'required' => false,
            ]) ?>
            <?= $this->Form->control('email_group_id', [
                'type' => 'select',
                'label' => __('Linked email group'),
                'options' => $emailGroups,
                'empty' => __('No linked email group'),
                'help' => __('The tag’s current members define who should be in this email group.'),
            ]) ?>
        </fieldset>
        <?= $this->element('Tags/assignments', compact(
            'roles',
            'emailGroups',
            'roleSelection',
            'teamSelectorData',
            'roleSelectorData',
            'assignmentRows',
        )) ?>
        <div class="workspace-form-footer">
            <?= $this->Form->button(__('Create tag')) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button button-outline']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
