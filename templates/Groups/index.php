<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Group> $groups
 */
?>
<div class="groups index content workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Groups'),
        'description' => __('Your district and Scout groups, kept up to date from core data.'),
        'actions' => [
        ],
    ]) ?>
    <?= $this->element('Workspace/index_filters', compact('filters', 'filterControls')) ?>
    <section class="workspace-table-panel" aria-label="<?= __('Groups') ?>">
    <div class="workspace-table-heading">
        <h2><?= __('Directory') ?></h2>
        <span><?= $this->Paginator->counter(__('{{count}} records')) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('group_name', __('Group')) ?></th>
                    <th><?= $this->Paginator->sort('type', __('Type')) ?></th>
                    <th><?= $this->Paginator->sort('sections_count', __('Sections')) ?></th>
                    <th><?= $this->Paginator->sort('teams_count', __('Teams')) ?></th>
                    <th><?= $this->Paginator->sort('roles_count', __('Roles')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($groups) === 0) : ?>
                <tr><td colspan="6" class="workspace-empty"><?= __('No records to display yet.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($groups as $item) : ?>
                    <?php $typeStatusClass = $item->type
                    ? 'workspace-group-type-' . $item->type->value
                    : 'workspace-status-muted'; ?>
                <tr>
                    <td><?= $this->Html->link($item->group_name, ['action' => 'view', $item->id]) ?></td>
                    <td>
                        <span class="workspace-status <?= $typeStatusClass ?>">
                            <?= h($item->type?->label() ?? __('Unclassified')) ?>
                        </span>
                    </td>
                    <td><?= $this->Number->format($item->sections_count) ?></td>
                    <td><?= $this->Number->format($item->teams_count) ?></td>
                    <td><?= $this->Number->format($item->roles_count) ?></td>
                    <td class="actions"><?= $this->Html->link(__('View'), ['action' => 'view', $item->id]) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <?= $this->element('Workspace/pagination_limit') ?>
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
        ) ?></p>
    </div>
    </section>
</div>
