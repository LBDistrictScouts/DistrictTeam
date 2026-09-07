<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 */
$this->assign('title', $role->name);
$this->Html->css('role-view', ['block' => true]);
$team = $role->team;
$appointments = $role->current_appointments;
$staffingStatus = $role->staffing_status;
$staffingStatusLabel = match ($staffingStatus) {
    'recruiting' => __('Recruiting'),
    'filled' => __('Filled'),
    'covered' => __('Covered'),
    default => __('Vacant'),
};
?>
<div class="role-detail">
    <nav class="role-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Roles'), ['action' => 'index']) ?>
        <?php if ($team) : ?>
            <span aria-hidden="true">/</span>
            <?= $this->Html->link($team->team_name, ['controller' => 'Teams', 'action' => 'view', $team->id]) ?>
        <?php endif; ?>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= h($role->name) ?></span>
    </nav>

    <header class="role-hero">
        <div>
            <p class="role-eyebrow"><?= __('Role overview') ?></p>
            <h1><?= h($role->name) ?></h1>
            <p class="role-hero-context">
                <?= $team ? h($team->team_name) : __('No team assigned') ?>
                <?php if ($role->is_lead) :
                    ?><span><?= __('Team lead') ?></span><?php
                endif; ?>
            </p>
        </div>
        <?= $this->Html->link(__('Edit role'), ['action' => 'edit', $role->id], ['class' => 'role-primary-link']) ?>
    </header>

    <div class="role-stats" aria-label="<?= __('Role at a glance') ?>">
        <div>
            <strong class="role-status role-status-<?= h($staffingStatus) ?>">
                <?= $staffingStatusLabel ?>
            </strong>
            <span><?= __('Current status') ?></span>
        </div>
        <div>
            <strong><?= $role->is_lead ? __('Yes') : __('No') ?></strong>
            <span><?= __('Team lead role') ?></span>
        </div>
        <div>
            <strong><?= $role->multi_member_role ? __('Yes') : __('No') ?></strong>
            <span><?= __('Multi Member Role') ?></span>
        </div>
        <div>
            <strong><?= $role->is_trustee_role ? __('Yes') : __('No') ?></strong>
            <span><?= __('Trustee Board role') ?></span>
        </div>
        <div>
            <strong><?= $this->Number->format(count($appointments)) ?></strong>
            <span><?= __n('Current appointment', 'Current appointments', count($appointments)) ?></span>
        </div>
    </div>

    <div class="role-detail-grid">
        <div class="role-main-panels">
            <section class="role-panel role-holder-panel" aria-labelledby="role-holder-heading">
                <p class="role-eyebrow"><?= __('Appointment') ?></p>
                <h2 id="role-holder-heading"><?= __('Current holders') ?></h2>
                <?php if ($appointments) : ?>
                    <ul class="role-holder-list">
                    <?php foreach ($appointments as $appointment) :
                        $member = $appointment->member;
                        $appointmentStart = $this->Time->format(
                            $appointment->effective_start_date,
                            'd MMMM yyyy',
                        );
                        $memberProfile = $this->Html->link(
                            $member->full_name,
                            ['controller' => 'Members', 'action' => 'view', $member->id],
                        );
                        ?>
                        <li>
                            <span class="role-avatar" aria-hidden="true">
                                <?= h(mb_substr($member->first_name, 0, 1) . mb_substr($member->last_name, 0, 1)) ?>
                            </span>
                            <div>
                                <strong><?= $memberProfile ?></strong>
                                <p><?= __('Appointed from {0}', $appointmentStart) ?></p>
                            </div>
                            <?= $this->Html->link(
                                __('View') . ' →',
                                ['controller' => 'Appointments', 'action' => 'view', $appointment->id],
                                ['class' => 'role-appointment-link'],
                            ) ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="role-empty"><?= __('This role does not currently have any appointments.') ?></p>
                    <?= $this->Html->link(
                        __('Create an appointment'),
                        ['controller' => 'Appointments', 'action' => 'add', '?' => ['role_id' => $role->id]],
                        ['class' => 'role-text-link'],
                    ) ?>
                <?php endif; ?>
            </section>

            <section class="role-panel" aria-labelledby="role-description-heading">
                <h2 id="role-description-heading"><?= __('About this role') ?></h2>
                <?php if ($role->description) : ?>
                    <p class="role-description"><?= nl2br(h($role->description)) ?></p>
                <?php else : ?>
                    <p class="role-empty"><?= __('No description has been added for this role.') ?></p>
                <?php endif; ?>
            </section>
        </div>

        <aside class="role-context-panels" aria-label="<?= __('Role context') ?>">
            <section class="role-panel" aria-labelledby="role-team-heading">
                <p class="role-eyebrow"><?= __('Organisation') ?></p>
                <h2 id="role-team-heading"><?= __('Team') ?></h2>
                <?php if ($team) : ?>
                    <?= $this->Html->link(
                        $team->team_name,
                        ['controller' => 'Teams', 'action' => 'view', $team->id],
                        ['class' => 'role-team-link'],
                    ) ?>
                    <dl class="role-metadata">
                        <dt><?= __('Group') ?></dt>
                        <dd><?php if ($team->group) : ?>
                            <?= $this->Html->link(
                                $team->group->group_name,
                                ['controller' => 'Groups', 'action' => 'view', $team->group->id],
                            ) ?>
                            <?php else : ?>
                            <?= __('Not assigned') ?>
                        <?php endif; ?></dd>
                        <?php if ($team->section) : ?>
                        <dt><?= __('Section') ?></dt>
                        <dd><?= h($team->section->section_name) ?></dd>
                        <?php endif; ?>
                    </dl>
                <?php else : ?>
                    <p class="role-empty"><?= __('This role has not been assigned to a team.') ?></p>
                <?php endif; ?>
            </section>

            <section class="role-panel">
                <h2><?= __('Role details') ?></h2>
                <dl class="role-metadata">
                    <dt><?= __('Role UUID') ?></dt><dd><code><?= h($role->id) ?></code></dd>
                    <dt><?= __('Slug') ?></dt><dd><code><?= h($role->slug) ?></code></dd>
                </dl>
            </section>
        </aside>
    </div>
</div>
