<?php
/**
 * @var \App\View\AppView $this
 * @var string $teamField
 * @var string $fieldPrefix
 * @var string $selectedTeamId
 * @var string $selectedGroupId
 * @var string $selectedSectionId
 * @var bool $deriveSectionFromTeam
 * @var bool $disableEmptyControls
 * @var bool $teamRequired
 * @var string $teamEmpty
 * @var array<string, mixed> $teamSelectorData
 */
$teamField ??= 'team_id';
$fieldPrefix ??= 'team_';
$selectedTeamId ??= '';
$selectedGroupId ??= '';
$selectedSectionId ??= '';
$deriveSectionFromTeam ??= true;
$disableEmptyControls ??= true;
$teamRequired ??= true;
$teamEmpty ??= __('Choose a team');
?>
<div class="team-selector" data-team-selector data-team-field="<?= h($teamField) ?>"
    data-selected-team-id="<?= h($selectedTeamId) ?>"
    data-selected-group-id="<?= h($selectedGroupId) ?>"
    data-selected-section-id="<?= h($selectedSectionId) ?>"
    data-derive-section-from-team="<?= $deriveSectionFromTeam ? 'true' : 'false' ?>"
    data-disable-empty-controls="<?= $disableEmptyControls ? 'true' : 'false' ?>"
    data-team-empty="<?= h($teamEmpty) ?>">
    <?= $this->Form->control($fieldPrefix . 'group_id', [
        'type' => 'select', 'label' => __('Group'), 'empty' => __('Choose a group'), 'required' => true,
    ]) ?>
    <?= $this->Form->control($fieldPrefix . 'section_id', [
        'type' => 'select', 'label' => __('Section'), 'empty' => __('Any section'), 'disabled' => true,
    ]) ?>
    <?= $this->Form->control($teamField, [
        'type' => 'select', 'label' => __('Team'), 'empty' => $teamEmpty,
        'disabled' => true, 'required' => $teamRequired,
    ]) ?>
    <script type="application/json" data-team-selector-data><?=
        json_encode($teamSelectorData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    ?></script>
</div>
