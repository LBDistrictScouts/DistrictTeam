<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Member> $members
 */
?>
<div class="members index content workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Members'),
        'description' => __('The people who make your district happen.'),
        'actions' => [
            ['label' => __('New Member'), 'url' => ['action' => 'add'], 'secondary' => false],
            ['label' => __('Upload CSV'), 'url' => ['action' => 'upload'], 'secondary' => true],
        ],
    ]) ?>
    <?= $this->element('Workspace/index_filters', compact('filters', 'filterControls')) ?>
    <section class="workspace-table-panel" aria-label="<?= __('Members') ?>">
    <div class="workspace-table-heading">
        <h2><?= __('Directory') ?></h2>
        <span><?= $this->Paginator->counter(__('{{count}} records')) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('first_name') ?></th>
                    <th><?= $this->Paginator->sort('last_name') ?></th>
                    <th><?= $this->Paginator->sort('membership_number') ?></th>
                    <th><?= __('Status') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($members) === 0): ?>
                <tr><td colspan="5" class="workspace-empty"><?= __('No records to display yet.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td><?= h($member->first_name) ?></td>
                    <td><?= h($member->last_name) ?></td>
                    <td><?= h($member->membership_number) ?></td>
                    <td><span class="workspace-status <?= $member->active ? 'workspace-status-positive' : 'workspace-status-muted' ?>"><?= $member->active ? __('Active') : __('Inactive') ?></span></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $member->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $member->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $member->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $member->id),
                            ]
                        ) ?>
                    </td>
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
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
    </section>
</div>
