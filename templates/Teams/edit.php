<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 * @var string[]|\Cake\Collection\CollectionInterface $parentTeam
 */
?>
<div class="row">
    <div class="column">
        <div class="teams form content">
            <?= $this->Form->create($team) ?>
            <fieldset>
                <legend><?= __('Edit Team') ?></legend>
                <?php
                    echo $this->Form->control('team_name');
                    echo $this->Form->control('team_parent_id', ['options' => $parentTeam, 'empty' => true]);
                    echo $this->Form->control('tree_left');
                    echo $this->Form->control('tree_right');
                    echo $this->Form->control('tree_level');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
