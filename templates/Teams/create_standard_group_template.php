<?php
/** @var \App\View\AppView $this @var list<array<string, mixed>> $teams @var list<array{team_name?: mixed, skip?: mixed}> $submittedTeams @var bool $reviewOverrides */
?>
<div class="teams create-standard-group-template workspace-page workspace-form-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>"><?= $this->Html->link(__('Back to teams'), ['action' => 'index']) ?></nav>
    <?= $this->element('Workspace/index_header', ['title' => __('Create standard Group template'), 'description' => __('Review the baseline Group leadership, section, and trustee structure.'), 'actions' => []]) ?>
    <section class="workspace-form-panel" aria-labelledby="standard-template-title">
        <h2 id="standard-template-title"><?= __('Proposed teams') ?></h2>
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'standard-template-review-toggle']) ?>
        <?= $this->Form->control('review_overrides', ['type' => 'checkbox', 'label' => __('Review name overrides'), 'checked' => $reviewOverrides, 'value' => '1', 'onchange' => 'this.form.submit()']) ?>
        <?= $this->Form->end() ?>
        <?php if ($teams === []) : ?>
            <p><?= $reviewOverrides ? __('No name overrides need review.') : __('All standard template records already exist.') ?></p>
        <?php else : ?>
            <p><?= $reviewOverrides ? __('Select overridden names to restore their standard template name.') : __('Change names or skip any records that should not be created.') ?></p>
            <?= $this->Form->create(null) ?>
            <?= $this->Form->hidden('review_overrides', ['value' => $reviewOverrides ? '1' : '0']) ?>
            <?php $groupedTeams = []; foreach ($teams as $index => $team) { $groupedTeams[$team['group_id']]['name'] = $team['group_name']; $groupedTeams[$team['group_id']]['items'][] = [$index, $team]; } foreach ($groupedTeams as $group) :
                $hasCreations = (bool)array_filter($group['items'], fn(array $item): bool => $item[1]['action'] === 'create');
                $hasOverrides = (bool)array_filter($group['items'], fn(array $item): bool => $item[1]['action'] === 'update'); ?>
                <details class="workspace-mapping-grid workspace-mapped-grid standard-template-group" open>
                    <summary><?= h($group['name']) ?> <span><?= count($group['items']) ?></span><?php if ($hasCreations) : ?><button type="button" class="button button-clear" data-template-skip-toggle data-skip-all-label="<?= __('Skip all') ?>" data-unskip-all-label="<?= __('Unskip all') ?>"><?= __('Skip all') ?></button><?php endif; ?><?php if ($hasOverrides) : ?><button type="button" class="button button-clear" data-template-apply-toggle data-apply-all-label="<?= __('Apply all') ?>" data-unapply-all-label="<?= __('Unapply all') ?>"><?= __('Apply all') ?></button><?php endif; ?></summary>
                    <div class="table-responsive"><table><thead><tr><th><?= __('Standard team') ?></th><?php if ($reviewOverrides) : ?><th><?= __('Current name') ?></th><?php endif; ?><th><?= __('Section') ?></th><th><?= __('Action') ?></th></tr></thead><tbody>
                    <?php foreach ($group['items'] as [$index, $team]) : $submitted = $submittedTeams[$index] ?? []; $isOverride = $team['action'] === 'update'; $name = is_string($submitted['team_name'] ?? null) ? $submitted['team_name'] : $team['team_name']; ?>
                        <tr><td><?php if ($isOverride) : ?><?= h($team['team_name']) ?><?= $this->Form->hidden('teams.' . $index . '.team_name', ['value' => $team['team_name']]) ?><?php else : ?><?= $this->Form->control('teams.' . $index . '.team_name', ['label' => false, 'value' => $name, 'aria-label' => __('Team name')]) ?><?php endif; ?></td><?php if ($reviewOverrides) : ?><td><?= $isOverride ? h($team['existing_name']) : '' ?></td><?php endif; ?><td><?= h($team['section_name'] ?? __('No section')) ?></td><td><?php if ($isOverride) : ?><?= $this->Form->control('teams.' . $index . '.apply', ['type' => 'checkbox', 'label' => __('Apply'), 'checked' => !empty($submitted['apply']), 'aria-label' => __('Apply standard name to {0}', $team['existing_name'])]) ?><?php else : ?><?= $this->Form->control('teams.' . $index . '.skip', ['type' => 'checkbox', 'label' => __('Skip'), 'checked' => !empty($submitted['skip']), 'data-template-skip' => true]) ?><?php endif; ?></td></tr>
                    <?php endforeach; ?></tbody></table></div>
                </details>
            <?php endforeach; ?>
            <div class="workspace-form-footer"><?= $this->Form->button($reviewOverrides ? __('Apply selected team names') : __('Create selected teams')) ?><?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button button-outline']) ?></div>
            <?= $this->Form->end() ?>
        <?php endif; ?>
    </section>
</div>
<?= $this->Html->script('standard-template-selection') ?>
