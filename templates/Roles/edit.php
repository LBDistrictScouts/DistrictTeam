<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 * @var string[]|\Cake\Collection\CollectionInterface $teams
 */
?>
<div class="row">
    <div class="column">
        <div class="roles form content">
            <?= $this->Form->create($role) ?>
            <fieldset>
                <legend><?= __('Edit Role') ?></legend>
                <?php
                    echo $this->Form->control('team_id', ['options' => $teams]);
                    echo $this->Form->control('name');
                    echo $this->Form->control('slug');
                    echo $this->Form->control('description');
                    echo $this->Form->control('is_lead', [
                        'type' => 'checkbox',
                        'label' => __('Team lead role'),
                    ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
