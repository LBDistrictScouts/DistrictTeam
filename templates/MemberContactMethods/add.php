<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MemberContactMethod $memberContactMethod
 * @var \Cake\Collection\CollectionInterface|string[] $members
 * @var array<int, string> $contactMethodTypes
 */
?>
<div class="row">
    <div class="column">
        <div class="memberContactMethods form content">
            <?= $this->Form->create($memberContactMethod) ?>
            <fieldset>
                <legend><?= __('Add Member Contact Method') ?></legend>
                <?php
                    echo $this->Form->control('member_id', ['options' => $members]);
                    echo $this->Form->control('contact_method');
                    echo $this->Form->control('contact_method_type', ['options' => $contactMethodTypes]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
