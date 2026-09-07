<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Appointment $appointment
 * @var \Cake\Collection\CollectionInterface|array<string> $roles
 * @var array<string, mixed> $roleSelectorData
 */
?>
<div class="appointment-role-selector" data-role-selector>
    <?= $this->Form->control('role_group_id', [
        'label' => __('Group'),
        'empty' => __('Choose a group'),
        'required' => true,
    ]) ?>
    <?= $this->Form->control('role_section_id', [
        'label' => __('Section'),
        'empty' => __('Any section'),
        'disabled' => true,
    ]) ?>
    <?= $this->Form->control('role_team_id', [
        'label' => __('Team'),
        'empty' => __('Choose a team'),
        'disabled' => true,
        'required' => true,
    ]) ?>
    <?= $this->Form->control('role_id', [
        'label' => __('Role'),
        'options' => $roles,
        'empty' => __('Choose a role'),
        'disabled' => true,
        'required' => true,
    ]) ?>
</div>
<script type="application/json" data-role-selector-data><?=
    json_encode($roleSelectorData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
