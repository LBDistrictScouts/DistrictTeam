<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Group $group
 */
$this->assign('title', $group->group_name);
$this->Html->css('group-view', ['block' => true]);
$teams = $group->teams;
$roles = array_merge(...array_map(fn($team) => $team->roles, $teams)) ?: [];
$filledRoles = count(array_filter($roles, fn($role) => $role->currently_filled));
$type = $group->type?->value;
$typeLabel = $group->type?->label() ?? __('Unclassified');
?>
<div class="group-detail">
    <nav class="group-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Groups'), ['action' => 'index']) ?>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= h($group->group_name) ?></span>
    </nav>

    <header class="group-hero">
        <div>
            <p class="group-eyebrow"><?= __('Group overview') ?></p>
            <h1><?= h($group->group_name) ?></h1>
            <p class="group-hero-context">
                <span class="group-type-pill <?= $type ? 'group-type-' . h($type) : 'group-type-unclassified' ?>">
                    <?= h($typeLabel) ?>
                </span>
            </p>
        </div>
    </header>

    <div class="group-stats" aria-label="<?= __('Group at a glance') ?>">
        <?php foreach (
        [
            [count($group->sections), __('Sections')],
            [count($teams), __('Teams')],
            [count($roles), __('Roles')],
            [$filledRoles, __('Filled roles')],
        ] as [$value, $label]
) : ?>
        <div><strong><?= $this->Number->format($value) ?></strong><span><?= $label ?></span></div>
        <?php endforeach; ?>
    </div>

    <div class="group-detail-grid">
        <div class="group-main-panels">
            <section class="group-panel" aria-labelledby="group-sections-heading">
                <div class="group-panel-heading">
                    <h2 id="group-sections-heading"><?= __('Sections') ?></h2>
                    <?= $this->Html->link(
                        __('All sections') . ' →',
                        ['controller' => 'Sections', 'action' => 'index'],
                    ) ?>
                </div>
                <?php if ($group->sections) : ?>
                <ul class="group-section-list">
                    <?php foreach ($group->sections as $section) : ?>
                    <li>
                        <div>
                            <strong><?= h($section->section_name) ?></strong>
                            <p><?= h($section->section_type->label()) ?></p>
                        </div>
                        <?php if ($section->meeting_day || $section->meeting_start_time) : ?>
                        <span class="group-meeting-time">
                            <?= h(implode(' · ', array_filter([
                                $section->meeting_day,
                                implode(' – ', array_filter([
                                    $section->meeting_start_time,
                                    $section->meeting_end_time,
                                ])),
                            ]))) ?>
                        </span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                <p class="group-empty"><?= __('No sections have been imported for this group yet.') ?></p>
                <?php endif; ?>
            </section>

            <section class="group-panel" aria-labelledby="group-teams-heading">
                <div class="group-panel-heading">
                    <h2 id="group-teams-heading"><?= __('Teams') ?></h2>
                    <?= $this->Html->link(
                        __('All teams') . ' →',
                        ['controller' => 'Teams', 'action' => 'index'],
                    ) ?>
                </div>
                <?php if ($teams) : ?>
                <ul class="group-team-list">
                    <?php foreach ($teams as $team) :
                        $teamRoles = $team->roles;
                        $filledTeamRoles = count(array_filter($teamRoles, fn($role) => $role->currently_filled));
                        ?>
                    <li>
                        <div>
                            <?= $this->Html->link(
                                $team->team_name,
                                ['controller' => 'Teams', 'action' => 'view', $team->id],
                                ['class' => 'group-team-name'],
                            ) ?>
                            <p>
                                <?php if ($team->section) :
                                    ?><?= h($team->section->section_name) ?> · <?php
                                endif; ?>
                                <?= __n(
                                    '{0} role',
                                    '{0} roles',
                                    count($teamRoles),
                                    $this->Number->format(count($teamRoles)),
                                ) ?>
                            </p>
                        </div>
                        <span class="group-role-status">
                            <?= __n(
                                '{0} filled',
                                '{0} filled',
                                $filledTeamRoles,
                                $this->Number->format($filledTeamRoles),
                            ) ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                <p class="group-empty"><?= __('No teams have been added to this group yet.') ?></p>
                <?php endif; ?>
            </section>
        </div>

        <aside class="group-context-panels">
            <section class="group-panel" aria-labelledby="group-details-heading">
                <h2 id="group-details-heading"><?= __('Group details') ?></h2>
                <dl class="group-metadata">
                    <dt><?= __('Type') ?></dt>
                    <dd>
                        <span class="group-type-pill group-type-detail <?=
                            $type ? 'group-type-' . h($type) : 'group-type-unclassified'
                        ?>">
                            <?= h($typeLabel) ?>
                        </span>
                    </dd>
                    <dt><?= __('OSM ID') ?></dt>
                    <dd><?= h($group->group_osm_id ?? __('Not available')) ?></dd>
                    <dt><?= __('Domains') ?></dt>
                    <dd>
                        <?php if ($group->domains) : ?>
                            <ul class="group-domain-list">
                            <?php foreach ($group->domains as $domain) : ?>
                                <li><?= h($domain) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <?= __('Not available') ?>
                        <?php endif; ?>
                    </dd>
                </dl>
            </section>
        </aside>
    </div>
</div>
