<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MemberContactMethod $memberContactMethod
 */
?>
<div class="row">
    <div class="column">
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
