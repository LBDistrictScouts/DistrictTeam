<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Role> $roles
 */
?>
<div class="roles index content workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Roles'),
        'description' => __('See the responsibilities across your teams and where help is needed.'),
        'actions' => [
            ['label' => __('New Role'), 'url' => ['action' => 'add'], 'secondary' => false],
            ['label' => __('Create standard roles'), 'url' => ['action' => 'createStandardGroupTemplate'], 'secondary' => true],
        ],
    ]) ?>
    <section class="workspace-table-panel" aria-label="<?= __('Roles') ?>">
    <div class="workspace-table-heading">
        <h2><?= __('Directory') ?></h2>
        <span><?= $this->Paginator->counter(__('{{count}} records')) ?></span>
    </div>
    <div class="table-responsive">
        <table class="roles-table">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('team_id') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('slug') ?></th>
                    <th><?= $this->Paginator->sort('currently_filled', __('Status')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($roles) === 0): ?>
                <tr><td colspan="5" class="workspace-empty"><?= __('No records to display yet.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td class="roles-identity"><?= $role->hasValue('team') ? str_repeat('>> ', $role->team->tree_level) . $this->Html->link($role->team->team_name, ['controller' => 'Teams', 'action' => 'view', $role->team->id]) : '' ?></td>
                    <td class="roles-identity"><?= h($role->name) ?></td>
                    <td class="roles-identity"><?= h($role->slug) ?></td>
                    <td>
                        <div class="roles-status-list">
                            <span class="workspace-status workspace-status-<?= h($role->staffing_status) ?>"><?= __(ucfirst($role->staffing_status)) ?></span>
                            <span class="workspace-status <?= $role->is_lead ? 'workspace-status-positive' : 'workspace-status-muted' ?>"><?= $role->is_lead ? __('Lead') : __('Not lead') ?></span>
                        </div>
                    </td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $role->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $role->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $role->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $role->id),
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
