<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Group|null $group
 * @var list<\App\Model\Entity\Role> $vacantRoles
 * @var list<\App\Model\Entity\Role> $coveredRoles
 * @var int $trusteeAppointmentCount
 * @var int $trusteeBoardTarget
 * @var list<\App\Model\Entity\Role> $trusteeBoardRoles
 * @var list<string> $missingTrusteeRoles
 * @var int $missingTrusteeMemberCount
 * @var bool $showTrusteeBoardGaps
 * @var list<\App\Model\Entity\MemberContactMethod> $nonGroupEmails
 */
$title = $group === null ? __('Report Card') : __('{0} Report Card', $group->group_name);
$this->assign('title', $title);
$this->Html->css('report-card', ['block' => true]);
?>
<div class="report-card">
    <nav class="report-card-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Groups'), ['action' => 'index']) ?>
        <span aria-hidden="true">/</span>
        <?php if ($group !== null) : ?>
            <?= $this->Html->link($group->group_name, ['action' => 'view', $group->id]) ?>
            <span aria-hidden="true">/</span>
        <?php endif; ?>
        <span aria-current="page"><?= __('Report Card') ?></span>
    </nav>

    <header class="report-card-hero">
        <p class="report-card-eyebrow"><?= h($group?->group_name ?? __('Groups')) ?></p>
        <h1><?= __('Report Card') ?></h1>
        <p><?= $group === null
            ? __('Focus on the roles and email addresses that need attention across your groups.')
            : __('Focus on the roles and email addresses that need attention in this group.') ?></p>
    </header>

    <section class="report-card-stats" aria-label="<?= __('Report summary') ?>">
        <article>
            <strong><?= $this->Number->format(count($vacantRoles)) ?></strong>
            <span><?= __n('Vacant role', 'Vacant roles', count($vacantRoles)) ?></span>
        </article>
        <article>
            <strong><?= $this->Number->format(count($coveredRoles)) ?></strong>
            <span><?= __n('Covered role', 'Covered roles', count($coveredRoles)) ?></span>
        </article>
        <article>
            <strong><?= $this->Number->format(count($nonGroupEmails)) ?></strong>
            <span><?= __n('Non-group email', 'Non-group emails', count($nonGroupEmails)) ?></span>
        </article>
        <article class="<?= $trusteeAppointmentCount < $trusteeBoardTarget ? 'report-card-stat-danger' : '' ?>">
            <strong><?= $this->Number->format($trusteeAppointmentCount) ?></strong>
            <span><?= __n(
                'Trustee Board appointment',
                'Trustee Board appointments',
                $trusteeAppointmentCount,
            ) ?></span>
        </article>
    </section>

    <?php if ($showTrusteeBoardGaps) : ?>
    <section class="report-card-panel report-card-trustee-panel" aria-labelledby="trustee-board-heading">
        <div class="report-card-panel-heading">
            <div>
                <p class="report-card-eyebrow"><?= __('Trustee Board') ?></p>
                <h2 id="trustee-board-heading"><?= __('Trustee Board appointments') ?></h2>
            </div>
        </div>
        <ul class="report-card-list">
            <?php foreach ($trusteeBoardRoles as $role) : ?>
                <?php foreach ($role->current_appointments as $appointment) : ?>
                    <?php $memberUrl = ['controller' => 'Members', 'action' => 'view', $appointment->member->id]; ?>
                <li>
                    <div>
                        <strong><?= $this->Html->link($appointment->member->full_name, $memberUrl) ?></strong>
                        <span><?= h($role->name) ?></span>
                    </div>
                    <span class="report-card-status"><?= __('Appointed') ?></span>
                </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php foreach ($missingTrusteeRoles as $roleName) : ?>
            <li class="report-card-missing-role">
                <div>
                    <strong><?= h($roleName) ?></strong>
                    <span><?= __('This Trustee Board role needs an appointment.') ?></span>
                </div>
                <span class="report-card-status report-card-status-attention"><?= __('Vacant') ?></span>
            </li>
            <?php endforeach; ?>
            <?php if ($missingTrusteeMemberCount > 0) : ?>
                <?php
                $memberGapMessage = __n(
                    '1 Trustee Board member place needs an appointment.',
                    '{0} Trustee Board member places need appointments.',
                    $missingTrusteeMemberCount,
                    $this->Number->format($missingTrusteeMemberCount),
                );
                ?>
            <li class="report-card-missing-role">
                <div>
                    <strong><?= __('Trustee Board Member') ?></strong>
                    <span><?= $memberGapMessage ?></span>
                </div>
                <span class="report-card-status report-card-status-attention"><?= __('Vacant') ?></span>
            </li>
            <?php endif; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if ($coveredRoles) : ?>
    <section class="report-card-panel report-card-covered-panel" aria-labelledby="covered-roles-heading">
        <div class="report-card-panel-heading">
            <div>
                <p class="report-card-eyebrow"><?= __('Staffing') ?></p>
                <h2 id="covered-roles-heading"><?= __('Covered roles') ?></h2>
            </div>
            <?= $this->Html->link(
                __('All roles') . ' →',
                ['controller' => 'Roles', 'action' => 'index', '?' => ['status' => 'covered']],
            ) ?>
        </div>
        <ul class="report-card-list">
            <?php foreach ($coveredRoles as $role) : ?>
                <?php $roleUrl = ['controller' => 'Roles', 'action' => 'view', $role->id]; ?>
            <li>
                <div>
                    <strong><?= $this->Html->link($role->name, $roleUrl) ?></strong>
                    <span><?= $group === null
                        ? h($role->group->group_name) . ' · '
                        : '' ?><?= h($role->team->team_name) ?></span>
                </div>
                <span class="report-card-status"><?= __('Covered until {0}', $this->Time->format(
                    $role->is_covered_until,
                    'd MMMM yyyy',
                )) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <div class="report-card-lists">
        <section class="report-card-panel" aria-labelledby="vacant-roles-heading">
            <div class="report-card-panel-heading">
                <div>
                    <p class="report-card-eyebrow"><?= __('Staffing') ?></p>
                    <h2 id="vacant-roles-heading"><?= __('Vacant roles') ?></h2>
                </div>
                <?= $this->Html->link(__('All roles') . ' →', ['controller' => 'Roles', 'action' => 'index']) ?>
            </div>
            <?php if ($vacantRoles) : ?>
            <ul class="report-card-list">
                <?php foreach ($vacantRoles as $role) : ?>
                    <?php $roleUrl = ['controller' => 'Roles', 'action' => 'view', $role->id]; ?>
                <li>
                    <div>
                        <strong><?= $this->Html->link($role->name, $roleUrl) ?></strong>
                        <span><?= $group === null
                            ? h($role->group->group_name) . ' · '
                            : '' ?><?= h($role->team->team_name) ?></span>
                    </div>
                    <span class="report-card-status report-card-status-attention"><?= __('Vacant') ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else : ?>
            <p class="report-card-empty"><?= __('There are no vacant roles.') ?></p>
            <?php endif; ?>
        </section>

        <section class="report-card-panel" aria-labelledby="non-group-emails-heading">
            <div class="report-card-panel-heading">
                <div>
                    <p class="report-card-eyebrow"><?= __('Contact details') ?></p>
                    <h2 id="non-group-emails-heading"><?= __('Non-group emails') ?></h2>
                </div>
                <?= $this->Html->link(__('All members') . ' →', ['controller' => 'Members', 'action' => 'index']) ?>
            </div>
            <?php if ($nonGroupEmails) : ?>
            <ul class="report-card-list">
                <?php foreach ($nonGroupEmails as $contactMethod) : ?>
                    <?php $memberUrl = ['controller' => 'Members', 'action' => 'view', $contactMethod->member->id]; ?>
                <li>
                    <div>
                        <strong><?= $this->Html->link($contactMethod->member->full_name, $memberUrl) ?></strong>
                        <span><?= h($contactMethod->contact_method) ?></span>
                    </div>
                    <span class="report-card-status"><?= h($contactMethod->contact_method_type->label()) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else : ?>
            <p class="report-card-empty"><?= __('There are no non-group email addresses.') ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>
