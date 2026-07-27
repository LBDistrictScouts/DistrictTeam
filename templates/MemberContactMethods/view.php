<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MemberContactMethod $memberContactMethod
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Member Contact Method'), ['action' => 'edit', $memberContactMethod->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Member Contact Method'), ['action' => 'delete', $memberContactMethod->id], ['confirm' => __('Are you sure you want to delete # {0}?', $memberContactMethod->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Member Contact Methods'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Member Contact Method'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="memberContactMethods view content">
            <h3><?= h($memberContactMethod->contact_method) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($memberContactMethod->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Member') ?></th>
                    <td><?= $memberContactMethod->hasValue('member') ? $this->Html->link($memberContactMethod->member->first_name, ['controller' => 'Members', 'action' => 'view', $memberContactMethod->member->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contact Method') ?></th>
                    <td><?= h($memberContactMethod->contact_method) ?></td>
                </tr>
                <tr>
                    <th><?= __('Contact Method Type') ?></th>
                    <td><?= h($memberContactMethod->contact_method_type->label()) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
