<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Appointment $appointment
 * @var string[]|\Cake\Collection\CollectionInterface $roles
 * @var string[]|\Cake\Collection\CollectionInterface $members
 * @var string[]|\Cake\Collection\CollectionInterface $memberContactMethods
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $appointment->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $appointment->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Appointments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="appointments form content">
            <?= $this->Form->create($appointment) ?>
            <fieldset>
                <legend><?= __('Edit Appointment') ?></legend>
                <?php
                    echo $this->Form->control('role_id', ['options' => $roles]);
                    echo $this->Form->control('member_id', ['options' => $members]);
                    echo $this->Form->control('member_contact_method_id', ['options' => $memberContactMethods]);
                    echo $this->Form->control('effective_start_date');
                    echo $this->Form->control('effective_end_date', ['empty' => true]);
                    echo $this->Form->control('active');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
