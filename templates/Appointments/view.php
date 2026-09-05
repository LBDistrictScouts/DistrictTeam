<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\Appointment $appointment */
$this->assign('title', __('Appointment'));
$this->Html->css('appointment-view', ['block' => true]);
$isCurrent = $appointment->active;
$startDate = $this->Time->format($appointment->effective_start_date, 'd MMMM yyyy');
$endDate = $appointment->effective_end_date
    ? $this->Time->format($appointment->effective_end_date, 'd MMMM yyyy')
    : null;
$member = $appointment->member;
?>
<div class="appointment-detail">
    <nav class="appointment-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Appointments'), ['action' => 'index']) ?>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= __('Appointment') ?></span>
    </nav>
    <header class="appointment-hero">
        <div>
            <p class="appointment-eyebrow"><?= __('Appointment') ?></p>
            <h1><?= h($appointment->role?->name ?? __('Role appointment')) ?></h1>
            <p class="appointment-hero-context"><?= h($member?->full_name ?? __('No member assigned')) ?></p>
        </div>
        <?= $this->Html->link(__('Edit appointment'), ['action' => 'edit', $appointment->id], ['class' => 'appointment-primary-link']) ?>
    </header>
    <div class="appointment-stats" aria-label="<?= __('Appointment at a glance') ?>">
        <div><strong class="appointment-status <?= $isCurrent ? 'appointment-status-current' : 'appointment-status-ended' ?>"><?= $isCurrent ? __('Current') : __('Ended') ?></strong><span><?= __('Appointment status') ?></span></div>
        <div><strong><?= h($startDate) ?></strong><span><?= __('Started') ?></span></div>
        <div><strong><?= $endDate ? h($endDate) : __('Open-ended') ?></strong><span><?= __('Ends') ?></span></div>
    </div>
    <div class="appointment-detail-grid">
        <section class="appointment-panel appointment-person-panel" aria-labelledby="appointment-member-heading">
            <p class="appointment-eyebrow"><?= __('Member') ?></p>
            <h2 id="appointment-member-heading"><?= __('Appointed member') ?></h2>
            <?php if ($member) : ?>
                <div class="appointment-person">
                    <span class="appointment-avatar" aria-hidden="true"><?= h(mb_substr($member->first_name, 0, 1) . mb_substr($member->last_name, 0, 1)) ?></span>
                    <div><strong><?= $this->Html->link($member->full_name, ['controller' => 'Members', 'action' => 'view', $member->id]) ?></strong><?= $this->Html->link(__('View member profile') . ' →', ['controller' => 'Members', 'action' => 'view', $member->id]) ?></div>
                </div>
            <?php else : ?>
                <p class="appointment-empty"><?= __('No member is assigned to this appointment.') ?></p>
            <?php endif; ?>
        </section>
        <aside class="appointment-context-panels" aria-label="<?= __('Appointment details') ?>">
            <section class="appointment-panel"><p class="appointment-eyebrow"><?= __('Role') ?></p><h2><?= __('Position') ?></h2><?php if ($appointment->role) : ?><?= $this->Html->link($appointment->role->name, ['controller' => 'Roles', 'action' => 'view', $appointment->role->id], ['class' => 'appointment-role-link']) ?><?php else : ?><p class="appointment-empty"><?= __('No role is assigned to this appointment.') ?></p><?php endif; ?></section>
            <section class="appointment-panel"><h2><?= __('Contact method') ?></h2><?php if ($appointment->member_contact_method) : ?><p class="appointment-contact-type"><?= h($appointment->member_contact_method->contact_method_type->label()) ?></p><p class="appointment-contact-value"><?= h($appointment->member_contact_method->contact_method) ?></p><?php else : ?><p class="appointment-empty"><?= __('No contact method is assigned.') ?></p><?php endif; ?></section>
            <section class="appointment-panel"><details class="appointment-record-details"><summary><?= __('Appointment record details') ?></summary><dl class="appointment-metadata"><dt><?= __('Appointment UUID') ?></dt><dd><code><?= h($appointment->id) ?></code></dd></dl></details></section>
        </aside>
    </div>
</div>
