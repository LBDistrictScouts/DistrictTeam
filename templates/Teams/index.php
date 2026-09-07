<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Team> $teams
 */
?>
<div class="teams index content workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Teams'),
        'description' => __('Keep your team structure clear and everyone connected.'),
        'actions' => [
            ['label' => __('New Team'), 'url' => ['action' => 'add'], 'secondary' => false],
            ['label' => __('Create standard Group template'), 'url' => ['action' => 'createStandardGroupTemplate'], 'secondary' => true],
            ['label' => __('Reorder teams'), 'url' => ['action' => 'reorder'], 'secondary' => true],
        ],
    ]) ?>
    <section class="workspace-table-panel" aria-label="<?= __('Teams') ?>">
    <div class="workspace-table-heading">
        <h2><?= __('Directory') ?></h2>
        <span><?= $this->Paginator->counter(__('{{count}} records')) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('team_name') ?></th>
                    <th><?= $this->Paginator->sort('team_parent_id') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($teams) === 0): ?>
                <tr><td colspan="3" class="workspace-empty"><?= __('No records to display yet.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($teams as $team): ?>
                <tr>
                    <td><?= str_repeat('>> ', $team->tree_level) ?><?= h($team->team_name) ?></td>
                    <td><?= $team->hasValue('parent_team') ? $this->Html->link($team->parent_team->team_name, ['controller' => 'Teams', 'action' => 'view', $team->parent_team->id]) : '' ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $team->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $team->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $team->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $team->id),
                            ]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
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
