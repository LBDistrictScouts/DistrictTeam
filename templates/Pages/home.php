<?php
/** @var \App\View\AppView $this */
/** @var array<string, int> $dashboardStats */

$this->assign('title', __('Dashboard'));
$this->Html->css('dashboard', ['block' => true]);
$filledPercentage = $dashboardStats['roles'] > 0
    ? (int)round($dashboardStats['filledRoles'] / $dashboardStats['roles'] * 100)
    : 0;
$quickActions = [
    ['＋', __('Add a member'), __('Create a new member record'), 'Members'],
    ['↗', __('Make an appointment'), __('Assign a member to a role'), 'Appointments'],
    ['◎', __('Make a role'), __('Add a role to a team'), 'Roles'],
    ['◇', __('Create a team'), __('Grow the team structure'), 'Teams'],
];
?>
<div class="dashboard">
    <section class="dashboard-hero">
        <div>
            <p class="dashboard-eyebrow"><?= __('District team workspace') ?></p>
            <h1><?= __('Your team, all in one place.') ?></h1>
            <p class="dashboard-intro">
                <?= __('Keep your structure clear, your roles covered and your member records up to date.') ?>
            </p>
        </div>
        <?= $this->Html->link(
            __('View team structure'),
            ['controller' => 'Teams', 'action' => 'index'],
            ['class' => 'dashboard-primary-action'],
        ) ?>
    </section>

    <section class="dashboard-stats" aria-label="<?= __('Team overview') ?>">
        <?php
        $stats = [
            ['◆', 'teams', __('Teams'), 'primary'],
            ['●', 'activeMembers', __('Active members'), ''],
            ['✓', 'filledRoles', __('Filled roles'), ''],
            ['!', 'vacantRoles', __('Vacancies'), 'attention'],
        ];
        foreach ($stats as [$icon, $key, $label, $modifier]) :
            ?>
        <article class="dashboard-stat<?= $modifier ? ' dashboard-stat--' . $modifier : '' ?>">
            <span class="dashboard-stat-icon" aria-hidden="true"><?= $icon ?></span>
            <div>
                <strong><?= $this->Number->format($dashboardStats[$key]) ?></strong>
                <span><?= $label ?></span>
            </div>
        </article>
        <?php endforeach; ?>
    </section>

    <div class="dashboard-grid">
        <section class="dashboard-panel dashboard-coverage">
            <div class="dashboard-panel-heading">
                <div>
                    <p class="dashboard-eyebrow"><?= __('Role coverage') ?></p>
                    <h2><?= __('Build a complete team') ?></h2>
                </div>
                <strong><?= $filledPercentage ?>%</strong>
            </div>
            <div
                class="dashboard-progress"
                role="progressbar"
                aria-valuenow="<?= $filledPercentage ?>"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <span style="width: <?= $filledPercentage ?>%"></span>
            </div>
            <p>
                <?= __(
                    '{0} of {1} roles are currently filled.',
                    $dashboardStats['filledRoles'],
                    $dashboardStats['roles'],
                ) ?>
            </p>
            <?= $this->Html->link(
                __('Review roles') . ' →',
                ['controller' => 'Roles', 'action' => 'index'],
                ['class' => 'dashboard-text-link'],
            ) ?>
        </section>

        <section class="dashboard-panel">
            <p class="dashboard-eyebrow"><?= __('Quick actions') ?></p>
            <h2><?= __('What would you like to do?') ?></h2>
            <nav class="dashboard-actions" aria-label="<?= __('Quick actions') ?>">
                <?php foreach ($quickActions as [$icon, $title, $description, $controller]) : ?>
                    <?= $this->Html->link(
                        '<span aria-hidden="true">' . $icon . '</span><span><strong>' . $title .
                            '</strong><small>' . $description . '</small></span>',
                        ['controller' => $controller, 'action' => 'add'],
                        ['escape' => false],
                    ) ?>
                <?php endforeach; ?>
            </nav>
        </section>
    </div>

    <section class="dashboard-directory">
        <div>
            <p class="dashboard-eyebrow"><?= __('Directory') ?></p>
            <h2><?= __('Find what you need') ?></h2>
        </div>
        <div class="dashboard-directory-links">
            <?= $this->Html->link(__('Teams'), ['controller' => 'Teams', 'action' => 'index']) ?>
            <?= $this->Html->link(__('Members'), ['controller' => 'Members', 'action' => 'index']) ?>
            <?= $this->Html->link(__('Roles'), ['controller' => 'Roles', 'action' => 'index']) ?>
            <?= $this->Html->link(__('Appointments'), ['controller' => 'Appointments', 'action' => 'index']) ?>
        </div>
    </section>
</div>
