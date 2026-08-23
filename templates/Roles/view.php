<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 */
?>
<div class="row">
    <div class="column">
        <div class="roles view content">
            <h3><?= h($role->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($role->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Team') ?></th>
                    <td><?= $role->hasValue('team') ? $this->Html->link($role->team->team_name, ['controller' => 'Teams', 'action' => 'view', $role->team->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($role->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Slug') ?></th>
                    <td><?= h($role->slug) ?></td>
                </tr>
                <tr>
                    <th><?= __('Description') ?></th>
                    <td><?= h($role->description) ?></td>
                </tr>
                <tr>
                    <th><?= __('Currently Filled') ?></th>
                    <td><?= $role->currently_filled ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Team Lead Role') ?></th>
                    <td><?= $role->is_lead ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
