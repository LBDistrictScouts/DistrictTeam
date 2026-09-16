<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Appointment $appointment
 * @var \Cake\Collection\CollectionInterface|array<string> $roles
 * @var array<string, mixed> $roleSelectorData
 * @var array<string, mixed> $teamSelectorData
 */
$selectedTeamId = '';
foreach ($roleSelectorData['roles'] as $roleData) {
    if ($roleData['id'] === $appointment->role_id) {
        $selectedTeamId = $roleData['teamId'];
        break;
    }
}
?>
<div class="team-selector-flow" data-role-selector>
    <?= $this->element('Teams/selector', [
        'teamField' => 'role_team_id',
        'fieldPrefix' => 'role_',
        'selectedTeamId' => $selectedTeamId,
        'teamSelectorData' => $teamSelectorData,
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
