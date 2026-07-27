<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Appointment $appointment
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Appointment'), ['action' => 'edit', $appointment->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Appointment'), ['action' => 'delete', $appointment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $appointment->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Appointments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Appointment'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="appointments view content">
            <h3><?= h($appointment->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($appointment->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Role') ?></th>
                    <td><?= $appointment->hasValue('role') ? $this->Html->link($appointment->role->name, ['controller' => 'Roles', 'action' => 'view', $appointment->role->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Member') ?></th>
                    <td><?= $appointment->hasValue('member') ? $this->Html->link($appointment->member->full_name, ['controller' => 'Members', 'action' => 'view', $appointment->member->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Member Contact Method') ?></th>
                    <td><?= $appointment->hasValue('member_contact_method') ? $this->Html->link($appointment->member_contact_method->contact_method, ['controller' => 'MemberContactMethods', 'action' => 'view', $appointment->member_contact_method->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Effective Start Date') ?></th>
                    <td><?= h($appointment->effective_start_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Effective End Date') ?></th>
                    <td><?= h($appointment->effective_end_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Active') ?></th>
                    <td><?= $appointment->active ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>