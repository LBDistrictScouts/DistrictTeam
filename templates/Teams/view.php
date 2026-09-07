<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 */
$this->assign('title', $team->team_name);
$this->Html->css('team-view', ['block' => true]);
$roles = $team->roles;
$filled = count(array_filter($roles, fn($role) => $role->staffing_status === 'filled'));
$recruiting = count(array_filter($roles, fn($role) => $role->staffing_status === 'recruiting'));
$vacant = count(array_filter($roles, fn($role) => $role->staffing_status === 'vacant'));
$lead = $team->team_lead;
$leadAppointments = $lead?->current_appointments ?? [];
$leader = $leadAppointments[0]?->member ?? null;
?>
<div class="team-detail">
    <nav class="team-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Teams'), ['action' => 'index']) ?>
        <?php if ($team->parent_team): ?>
            <span aria-hidden="true">/</span>
            <?= $this->Html->link($team->parent_team->team_name, ['action' => 'view', $team->parent_team->id]) ?>
        <?php endif; ?>
        <span aria-hidden="true">/</span><span aria-current="page"><?= h($team->team_name) ?></span>
    </nav>

    <header class="team-hero">
        <div>
            <p class="team-eyebrow"><?= __('Team overview') ?></p>
            <h1><?= h($team->team_name) ?></h1>
            <p class="team-hero-context">
                <?php if ($team->group) : ?>
                    <?= $this->Html->link(
                        $team->group->group_name,
                        ['controller' => 'Groups', 'action' => 'view', $team->group->id],
                    ) ?>
                <?php else : ?>
                    <?= __('No group assigned') ?>
                <?php endif; ?>
                <?php if ($team->section): ?> · <?= h($team->section->section_name) ?><?php endif; ?>
            </p>
        </div>
        <?= $this->Html->link(__('Edit team'), ['action' => 'edit', $team->id], ['class' => 'team-primary-link']) ?>
    </header>

    <div class="team-stats" aria-label="<?= __('Team at a glance') ?>">
        <?php foreach ([[count($roles), __('Roles')], [$filled, __('Filled roles')], [$recruiting, __('Recruiting roles')], [$vacant, __('Vacant roles')], [count($team->sub_teams), __('Child teams')]] as [$value, $label]): ?>
        <div><strong><?= $this->Number->format($value) ?></strong><span><?= $label ?></span></div>
        <?php endforeach; ?>
    </div>

    <div class="team-detail-grid">
        <div class="team-main-panels">
            <section class="team-panel team-lead-panel" aria-labelledby="team-lead-heading">
                <p class="team-eyebrow"><?= __('Leadership') ?></p>
                <h2 id="team-lead-heading"><?= __('Team lead') ?></h2>
                <?php if ($lead) : ?>
                    <div class="team-lead-identity">
                        <span class="team-avatar" aria-hidden="true"><?= $leader ? h(mb_substr($leader->first_name, 0, 1) . mb_substr($leader->last_name, 0, 1)) : '—' ?></span>
                        <div>
                            <strong>
                            <?php if ($leadAppointments) : ?>
                                <?php foreach ($leadAppointments as $index => $appointment) : ?>
                                    <?= $index ? ', ' : '' ?><?= $this->Html->link($appointment->member->full_name, ['controller' => 'Members', 'action' => 'view', $appointment->member->id]) ?>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <?= __('This role is vacant') ?>
                            <?php endif; ?>
                            </strong>
                            <?= $this->Html->link($lead->name, ['controller' => 'Roles', 'action' => 'view', $lead->id]) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="team-empty"><?= __('No lead role has been designated for this team.') ?></p>
                    <?= $this->Html->link(__('Manage roles'), ['controller' => 'Roles', 'action' => 'index'], ['class' => 'team-text-link']) ?>
                <?php endif; ?>
            </section>

            <section class="team-panel" aria-labelledby="team-roles-heading">
                <div class="team-panel-heading">
                    <h2 id="team-roles-heading"><?= __('Roles & people') ?></h2>
                    <?= $this->Html->link(__('All roles') . ' →', ['controller' => 'Roles', 'action' => 'index']) ?>
                </div>
                <?php if ($roles): ?>
                    <ul class="team-role-list">
                        <?php foreach ($roles as $role): $appointments = $role->current_appointments; ?>
                        <li>
                            <div>
                                <?= $this->Html->link($role->name, ['controller' => 'Roles', 'action' => 'view', $role->id], ['class' => 'team-role-name']) ?>
                                <?php if ($role->is_lead): ?><span class="team-lead-label"><?= __('Team lead') ?></span><?php endif; ?>
                                <p>
                                <?php if ($appointments) : ?>
                                    <?php foreach ($appointments as $index => $appointment) : ?>
                                        <?= $index ? ', ' : '' ?><?= $this->Html->link($appointment->member->full_name, ['controller' => 'Members', 'action' => 'view', $appointment->member->id]) ?>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <?= __('No current appointments') ?>
                                <?php endif; ?>
                                </p>
                            </div>
                            <span class="team-status team-status-<?= h($role->staffing_status) ?>">
                                <?= __($role->staffing_status === 'recruiting' ? 'Recruiting' : ucfirst($role->staffing_status)) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="team-empty"><?= __('No roles have been added to this team yet.') ?></p>
                    <?= $this->Html->link(__('Create a role'), ['controller' => 'Roles', 'action' => 'add'], ['class' => 'team-text-link']) ?>
                <?php endif; ?>
            </section>

            <section class="team-panel" aria-labelledby="team-children-heading">
                <div class="team-panel-heading">
                    <h2 id="team-children-heading"><?= __('Child teams') ?></h2>
                    <?= $this->Html->link(__('Reorder teams'), ['action' => 'reorder', $team->id]) ?>
                </div>
                <?php if ($team->sub_teams): ?>
                    <ul class="team-child-list">
                        <?php foreach ($team->sub_teams as $child): $childAppointments = $child->team_lead?->current_appointments ?? []; ?>
                        <li>
                            <div>
                                <?= $this->Html->link($child->team_name, ['action' => 'view', $child->id], ['class' => 'team-role-name']) ?>
                                <p><?= h($child->team_lead?->name ?? __('No lead role designated')) ?></p>
                                <?php if ($child->team_lead): ?>
                                    <small>
                                    <?php if ($childAppointments) : ?>
                                        <?php foreach ($childAppointments as $index => $appointment) : ?>
                                            <?= $index ? ', ' : '' ?><?= h($appointment->member->full_name) ?>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <?= __('Lead role vacant') ?>
                                    <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <?= $this->Html->link(__('View') . ' →', ['action' => 'view', $child->id], ['aria-label' => __('View {0}', $child->team_name)]) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="team-empty"><?= __('This team has no child teams.') ?></p>
                <?php endif; ?>
            </section>
        </div>

        <aside class="team-context-panels" aria-label="<?= __('Team context') ?>">
            <section class="team-panel">
                <p class="team-eyebrow"><?= __('Organisation') ?></p>
                <h2><?= __('Group & section') ?></h2>
                <dl class="team-metadata">
                    <dt><?= __('Group') ?></dt>
                    <dd><?php if ($team->group) : ?>
                        <?= $this->Html->link(
                            $team->group->group_name,
                            ['controller' => 'Groups', 'action' => 'view', $team->group->id],
                        ) ?>
                        <?php else : ?>
                            <?= __('Not assigned') ?>
                        <?php endif; ?>
                        <?php if ($team->group?->type): ?><span class="team-type-label"><?= h($team->group->type->label()) ?></span><?php endif; ?>
                    </dd>
                    <?php if ($team->group): ?>
                    <dt><?= __('Shared group UUID') ?></dt>
                    <dd><code><?= h($team->group_id) ?></code></dd>
                    <?php endif; ?>
                    <dt><?= __('Section') ?></dt>
                    <dd><?= h($team->section?->section_name ?? __('No section assigned')) ?></dd>
                    <dt><?= __('Slug') ?></dt>
                    <dd><code><?= h($team->slug) ?></code></dd>
                    <?php if ($team->section): ?>
                    <dt><?= __('Shared section UUID') ?></dt>
                    <dd><code><?= h($team->section_id) ?></code></dd>
                    <?php endif; ?>
                </dl>
            </section>
            <section class="team-panel">
                <h2><?= __('In the team structure') ?></h2>
                <?php if ($team->parent_team): ?>
                    <p class="team-muted"><?= __('Part of') ?></p>
                    <?= $this->Html->link($team->parent_team->team_name, ['action' => 'view', $team->parent_team->id], ['class' => 'team-text-link']) ?>
                <?php else: ?>
                    <p class="team-empty"><?= __('This is a top-level team.') ?></p>
                <?php endif; ?>
                <details class="team-record-details">
                    <summary><?= __('Team record details') ?></summary>
                    <dl class="team-metadata">
                        <dt><?= __('Team UUID') ?></dt><dd><code><?= h($team->id) ?></code></dd>
                        <dt><?= __('Sort order') ?></dt><dd><?= $this->Number->format($team->sort_order) ?></dd>
                    </dl>
                </details>
            </section>
        </aside>
    </div>
</div>
