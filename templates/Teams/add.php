<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 * @var \Cake\Collection\CollectionInterface|string[] $parentTeam
 */
?>
<div class="row">
    <div class="column">
        <div class="teams form content">
            <?= $this->Form->create($team) ?>
            <fieldset>
                <legend><?= __('Add Team') ?></legend>
                <?php
                    echo $this->Form->control('team_name');
                    echo $this->Form->control('team_parent_id', ['options' => $parentTeam, 'empty' => true]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
