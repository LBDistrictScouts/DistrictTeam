<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Appointment> $appointments
 */
?>
<div class="appointments index content workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Appointments'),
        'description' => __('Connect members with roles and manage their appointments.'),
        'actions' => [
            ['label' => __('New Appointment'), 'url' => ['action' => 'add'], 'secondary' => false],
        ],
    ]) ?>
    <section class="workspace-table-panel" aria-label="<?= __('Appointments') ?>">
    <div class="workspace-table-heading">
        <h2><?= __('Directory') ?></h2>
        <span><?= $this->Paginator->counter(__('{{count}} records')) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('role_id') ?></th>
                    <th><?= $this->Paginator->sort('member_id') ?></th>
                    <th><?= $this->Paginator->sort('member_contact_method_id') ?></th>
                    <th><?= __('Status') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($appointments) === 0): ?>
                <tr><td colspan="5" class="workspace-empty"><?= __('No records to display yet.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= $appointment->hasValue('role') ? $this->Html->link($appointment->role->name, ['controller' => 'Roles', 'action' => 'view', $appointment->role->id]) : '' ?></td>
                    <td><?= $appointment->hasValue('member') ? $this->Html->link($appointment->member->full_name, ['controller' => 'Members', 'action' => 'view', $appointment->member->id]) : '' ?></td>
                    <td><?= $appointment->hasValue('member_contact_method') ? h($appointment->member_contact_method->contact_method) : '' ?></td>
                    <td><span class="workspace-status <?= $appointment->active ? 'workspace-status-positive' : 'workspace-status-muted' ?>"><?= $appointment->active ? __('Active') : __('Ended') ?></span></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $appointment->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $appointment->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $appointment->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $appointment->id),
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
