<?php
/** @var \App\View\AppView $this @var list<array<string, mixed>> $roles @var list<array{role_name?: mixed, skip?: mixed}> $submittedRoles @var bool $reviewOverrides */
?>
<div class="roles create-standard-group-template workspace-page workspace-form-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>"><?= $this->Html->link(__('Back to roles'), ['action' => 'index']) ?></nav>
    <?= $this->element('Workspace/index_header', ['title' => __('Create standard roles'), 'description' => __('Review roles for standard teams.'), 'actions' => []]) ?>
    <section class="workspace-form-panel" aria-labelledby="standard-roles-title">
        <h2 id="standard-roles-title"><?= __('Proposed roles') ?></h2>
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'standard-template-review-toggle']) ?>
        <?= $this->Form->control('review_overrides', ['type' => 'checkbox', 'label' => __('Review name overrides'), 'checked' => $reviewOverrides, 'value' => '1', 'onchange' => 'this.form.submit()']) ?>
        <?= $this->Form->end() ?>
        <?php if ($roles === []) : ?>
            <p><?= $reviewOverrides ? __('No name overrides need review.') : __('No standard roles are waiting to be created. Create standard teams first, if needed.') ?></p>
        <?php else : ?>
            <p><?= $reviewOverrides ? __('Select overridden names to restore their standard template name.') : __('Change names or skip any roles that should not be created.') ?></p>
            <?= $this->Form->create(null) ?>
            <?= $this->Form->hidden('review_overrides', ['value' => $reviewOverrides ? '1' : '0']) ?>
            <?php $groupedRoles = []; foreach ($roles as $index => $role) { $groupedRoles[$role['group_id']]['name'] = $role['group_name']; $groupedRoles[$role['group_id']]['items'][] = [$index, $role]; } foreach ($groupedRoles as $group) :
                $hasCreations = (bool)array_filter($group['items'], fn(array $item): bool => $item[1]['action'] === 'create');
                $hasOverrides = (bool)array_filter($group['items'], fn(array $item): bool => $item[1]['action'] === 'update'); ?>
                <details class="workspace-mapping-grid workspace-mapped-grid standard-template-group" open>
                    <summary><?= h($group['name']) ?> <span><?= count($group['items']) ?></span><?php if ($hasCreations) : ?><button type="button" class="button button-clear" data-template-skip-toggle data-skip-all-label="<?= __('Skip all') ?>" data-unskip-all-label="<?= __('Unskip all') ?>"><?= __('Skip all') ?></button><?php endif; ?><?php if ($hasOverrides) : ?><button type="button" class="button button-clear" data-template-apply-toggle data-apply-all-label="<?= __('Apply all') ?>" data-unapply-all-label="<?= __('Unapply all') ?>"><?= __('Apply all') ?></button><?php endif; ?></summary>
                    <div class="table-responsive"><table><thead><tr><th><?= __('Standard role') ?></th><?php if ($reviewOverrides) : ?><th><?= __('Current name') ?></th><?php endif; ?><th><?= __('Team') ?></th><th><?= __('Action') ?></th></tr></thead><tbody>
                    <?php foreach ($group['items'] as [$index, $role]) : $submitted = $submittedRoles[$index] ?? []; $isOverride = $role['action'] === 'update'; $name = is_string($submitted['role_name'] ?? null) ? $submitted['role_name'] : $role['role_name']; ?>
                        <tr><td><?php if ($isOverride) : ?><?= h($role['role_name']) ?><?= $this->Form->hidden('roles.' . $index . '.role_name', ['value' => $role['role_name']]) ?><?php else : ?><?= $this->Form->control('roles.' . $index . '.role_name', ['label' => false, 'value' => $name, 'aria-label' => __('Role name')]) ?><?php endif; ?></td><?php if ($reviewOverrides) : ?><td><?= $isOverride ? h($role['existing_name']) : '' ?></td><?php endif; ?><td><?= h($role['team_name']) ?></td><td><?php if ($isOverride) : ?><?= $this->Form->control('roles.' . $index . '.apply', ['type' => 'checkbox', 'label' => __('Apply'), 'checked' => !empty($submitted['apply'])]) ?><?php else : ?><?= $this->Form->control('roles.' . $index . '.skip', ['type' => 'checkbox', 'label' => __('Skip'), 'checked' => !empty($submitted['skip']), 'data-template-skip' => true]) ?><?php endif; ?></td></tr>
                    <?php endforeach; ?></tbody></table></div>
                </details>
            <?php endforeach; ?>
            <div class="workspace-form-footer"><?= $this->Form->button($reviewOverrides ? __('Apply selected role names') : __('Create selected roles')) ?><?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button button-outline']) ?></div>
            <?= $this->Form->end() ?>
        <?php endif; ?>
    </section>
</div>
<?= $this->Html->script('standard-template-selection') ?>
