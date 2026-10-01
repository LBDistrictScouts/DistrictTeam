<?php
/** @var \App\View\AppView $this */
/** @var iterable<\App\Model\Entity\EmailGroup> $emailGroups */
?>
<div class="email-groups index content workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Email Groups'),
        'description' => __('Find email groups and the organisation areas they serve.'),
        'actions' => [
            ['label' => __('Create email group'), 'url' => ['action' => 'add'], 'secondary' => false],
        ],
    ]) ?>
    <?= $this->element('Workspace/index_filters', compact('filters', 'filterControls')) ?>
    <section class="workspace-table-panel" aria-label="<?= __('Email Groups') ?>">
        <div class="workspace-table-heading">
            <h2><?= __('Directory') ?></h2>
            <span><?= $this->Paginator->counter(__('{{count}} records')) ?></span>
        </div>
        <div class="table-responsive">
            <table><thead><tr>
                <th><?= $this->Paginator->sort('email_group_name', __('Email Group')) ?></th>
                <th><?= $this->Paginator->sort('email_address', __('Email Address')) ?></th>
                <th><?= $this->Paginator->sort('Groups.group_name', __('Group')) ?></th>
                <th><?= $this->Paginator->sort('Sections.section_name', __('Section')) ?></th>
                <th><?= $this->Paginator->sort('Teams.team_name', __('Team')) ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr></thead><tbody>
                <?php if (count($emailGroups) === 0) : ?>
                <tr><td colspan="6" class="workspace-empty"><?= __('No records to display yet.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($emailGroups as $emailGroup) : ?>
                <tr>
                    <td><?= h($emailGroup->email_group_name) ?></td>
                    <td><?= h($emailGroup->email_address) ?></td>
                    <td><?= h($emailGroup->group->group_name) ?></td>
                    <td><?= h($emailGroup->section?->section_name) ?></td>
                    <td><?= h($emailGroup->team?->team_name) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $emailGroup->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $emailGroup->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete {0}?', $emailGroup->email_group_name),
                            ],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody></table>
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
