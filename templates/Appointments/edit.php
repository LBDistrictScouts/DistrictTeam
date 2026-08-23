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
    <div class="column">
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
