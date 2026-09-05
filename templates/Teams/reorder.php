<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Team> $teams
 * @var \App\Model\Entity\Team|null $parentTeam
 * @var array $returnUrl
 */
$this->assign('title', __('Reorder teams'));
$this->Html->css(['workspace', 'team-order'], ['block' => true]);
?>
<div class="teams workspace-page reorder-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link($parentTeam ? __('Back to {0}', $parentTeam->team_name) : __('Back to teams'), $returnUrl) ?>
    </nav>
    <header class="workspace-hero">
    <div>
    <p class="workspace-eyebrow"><?= __('Team structure') ?></p>
    <h1><?= $parentTeam ? h(__('Reorder teams within {0}', $parentTeam->team_name)) : __('Reorder teams') ?></h1>
    <p class="workspace-description"><?= __('Drag a handle or use the arrow buttons to move teams with the same parent. Child teams move with their parent.') ?></p>
    </div>
    </header>
    <div class="workspace-reorder-panel">
    <?= $this->Form->create(null, ['id' => 'team-order-form', 'url' => ['action' => 'reorder', $parentTeam?->id]]) ?>
    <div id="team-order-tree">
        <div class="team-order-toolbar">
            <button type="button" class="button-outline" data-collapse-all="true"><?= __('Collapse all') ?></button>
            <button type="button" class="button-outline" data-collapse-all="false"><?= __('Expand all') ?></button>
        </div>
        <?= $this->element('Teams/order_list', ['teams' => $teams]) ?>
    </div>
    <div class="workspace-save-bar">
    <p id="team-order-status" role="status" aria-live="polite"><?= __('Changes are saved when you select Save order.') ?></p>
    <?= $this->Form->button(__('Save order')) ?>
    <?= $this->Html->link(__('Cancel'), $returnUrl, ['class' => 'button button-outline']) ?>
    </div>
    <?= $this->Form->end() ?>
    </div>
</div>
<?= $this->Html->script('team-order') ?>
