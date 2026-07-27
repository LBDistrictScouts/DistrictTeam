<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\MemberContactMethod> $memberContactMethods
 */
?>
<div class="memberContactMethods index content">
    <?= $this->Html->link(__('New Member Contact Method'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Member Contact Methods') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('member_id') ?></th>
                    <th><?= $this->Paginator->sort('contact_method') ?></th>
                    <th><?= $this->Paginator->sort('contact_method_type') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($memberContactMethods as $memberContactMethod): ?>
                <tr>
                    <td><?= $memberContactMethod->hasValue('member') ? $this->Html->link($memberContactMethod->member->full_name, ['controller' => 'Members', 'action' => 'view', $memberContactMethod->member->id]) : '' ?></td>
                    <td><?= h($memberContactMethod->contact_method) ?></td>
                    <td><?= h($memberContactMethod->contact_method_type->label()) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $memberContactMethod->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $memberContactMethod->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $memberContactMethod->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $memberContactMethod->id),
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
</div>
