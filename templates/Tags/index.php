<?php
/** @var \App\View\AppView $this */
/** @var iterable<\App\Model\Entity\Tag> $tags */
$this->assign('title', __('Tags'));
?>
<div class="workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Tags'),
        'description' => __('Manage the labels used to organise teams, roles and appointments.'),
        'actions' => [['label' => __('Add tag'), 'url' => ['action' => 'add']]],
    ]) ?>
    <section class="workspace-table-panel" aria-label="<?= __('Tags') ?>">
        <div class="workspace-table-heading">
            <h2><?= __('Directory') ?></h2>
            <span><?= $this->Paginator->counter(__('{{count}} records')) ?></span>
        </div>
        <div class="table-responsive">
        <table>
            <thead><tr><th><?= __('Tag label') ?></th><th><?= __('Tag code') ?></th><th><?= __('Cognito permissions group') ?></th><th><?= __('Actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($tags as $tag) : ?>
                <tr>
                    <td><?= $this->Html->link($tag->tag_label, ['action' => 'view', $tag->id], ['class' => 'user-tag']) ?></td>
                    <td><code><?= h($tag->tag_code) ?></code></td>
                    <td><?= $tag->is_cognito_permissions_group ? __('Yes') : __('No') ?></td>
                    <td><?= $this->Html->link(__('View'), ['action' => 'view', $tag->id]) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($tags) === 0) : ?><tr><td colspan="4"><?= __('No tags have been created yet.') ?></td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>
    <div class="paginator">
        <?= $this->element('Workspace/pagination_limit') ?>
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
