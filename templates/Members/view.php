<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\Member $member */
/** @var array<int, string> $contactMethodTypes */
$this->assign('title', $member->full_name);
$this->Html->css('member-view', ['block' => true]);
$this->Html->css('contact-method-modal', ['block' => true]);
$this->Html->script('contact-method-form', ['block' => true]);
$this->Html->script('contact-method-modal', ['block' => true]);
$appointments = $member->appointments ?? [];
$currentAppointments = array_filter($appointments, fn($appointment) => $appointment->active);
$joinDate = $this->Time->format($member->join_date, 'd MMMM yyyy');
$deleteContactMethodIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16M10 11v6m4-6v6M9 7l1-2h4l1 2m-9 0 1 13h10l1-13"/></svg>';
?>
<div class="member-detail">
    <nav class="member-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Members'), ['action' => 'index']) ?>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= h($member->full_name) ?></span>
    </nav>
    <header class="member-hero">
        <div><p class="member-eyebrow"><?= __('Member profile') ?></p><h1><?= h($member->full_name) ?></h1><p class="member-hero-context"><?= __('Member #{0}', $member->membership_number) ?></p></div>
        <?= $this->Html->link(__('Edit member'), ['action' => 'edit', $member->id], ['class' => 'member-primary-link']) ?>
    </header>
    <div class="member-stats" aria-label="<?= __('Member at a glance') ?>">
        <div><strong class="member-status <?= $member->active ? 'member-status-active' : 'member-status-inactive' ?>"><?= $member->active ? __('Active') : __('Inactive') ?></strong><span><?= __('Membership status') ?></span></div>
        <div><strong><?= $this->Number->format(count($currentAppointments)) ?></strong><span><?= __n('Current role', 'Current roles', count($currentAppointments)) ?></span></div>
        <div><strong><?= $this->Number->format(count($member->member_contact_methods)) ?></strong><span><?= __n('Contact method', 'Contact methods', count($member->member_contact_methods)) ?></span></div>
    </div>
    <div class="member-detail-grid">
        <div class="member-main-panels">
            <section class="member-panel" aria-labelledby="member-appointments-heading">
                <div class="member-panel-heading"><h2 id="member-appointments-heading"><?= __('Appointments') ?></h2><?= $this->Html->link(__('New appointment') . ' →', ['controller' => 'Appointments', 'action' => 'add', '?' => ['member_id' => $member->id]]) ?></div>
                <?php if ($appointments) : ?><ul class="member-appointment-list">
                    <?php foreach ($appointments as $appointment) : ?><li>
                        <div><strong><?= $appointment->role ? $this->Html->link($appointment->role->name, ['controller' => 'Roles', 'action' => 'view', $appointment->role->id]) : __('Role unavailable') ?></strong><p><?= __('From {0}', $this->Time->format($appointment->effective_start_date, 'd MMM yyyy')) ?><?= $appointment->effective_end_date ? __(' to {0}', $this->Time->format($appointment->effective_end_date, 'd MMM yyyy')) : '' ?></p></div>
                        <span class="member-appointment-status <?= $appointment->active ? 'member-appointment-current' : 'member-appointment-ended' ?>"><?= $appointment->active ? __('Current') : __('Ended') ?></span>
                        <?= $this->Html->link(__('View') . ' →', ['controller' => 'Appointments', 'action' => 'view', $appointment->id]) ?>
                    </li><?php endforeach; ?>
                </ul><?php else : ?><p class="member-empty"><?= __('This member does not have any appointments yet.') ?></p><?php endif; ?>
            </section>
            <section class="member-panel" aria-labelledby="member-contact-heading">
                <div class="member-panel-heading"><h2 id="member-contact-heading"><?= __('Contact methods') ?></h2><?= $this->element('Members/contact_method_button', ['modalId' => 'member-contact-method-modal', 'class' => 'member-contact-add']) ?></div>
                <?php if ($member->member_contact_methods) : ?><ul class="member-contact-list" id="contact-methods"><?php foreach ($member->member_contact_methods as $memberContactMethod) : ?><li<?= $memberContactMethod->is_non_group_email ? ' class="member-contact-non-group-email"' : '' ?>><span class="member-contact-type"><?= h($memberContactMethod->contact_method_type->label()) ?><?php if ($memberContactMethod->is_non_group_email) : ?><span class="member-contact-warning"><?= __('Non-group email') ?></span><?php endif; ?></span><strong><?= h($memberContactMethod->contact_method) ?></strong><?= $this->Form->postLink($deleteContactMethodIcon, ['controller' => 'MemberContactMethods', 'action' => 'deleteForMember', $member->id, $memberContactMethod->id], ['class' => 'member-contact-delete', 'escape' => false, 'title' => __('Delete contact method'), 'aria-label' => __('Delete {0}', $memberContactMethod->contact_method), 'confirm' => __('Are you sure you want to delete this contact method?')]) ?></li><?php endforeach; ?></ul><?php else : ?><p class="member-empty" id="contact-methods-empty"><?= __('No contact methods have been added yet.') ?></p><?php endif; ?>
            </section>
        </div>
        <aside class="member-context-panels" aria-label="<?= __('Member details') ?>">
            <section class="member-panel"><h2><?= __('Membership details') ?></h2><dl class="member-metadata"><dt><?= __('Membership number') ?></dt><dd><?= h($member->membership_number) ?></dd><dt><?= __('Joined') ?></dt><dd><?= h($joinDate) ?></dd><dt><?= __('Left') ?></dt><dd><?= $member->leave_date ? h($this->Time->format($member->leave_date, 'd MMMM yyyy')) : __('Still a member') ?></dd></dl></section>
            <section class="member-panel"><details class="member-record-details"><summary><?= __('Member record details') ?></summary><dl class="member-metadata"><dt><?= __('Member UUID') ?></dt><dd><code><?= h($member->id) ?></code></dd></dl></details></section>
        </aside>
    </div>
</div>
<?= $this->element('Members/contact_method_modal', [
    'modalId' => 'member-contact-method-modal',
    'formId' => 'add-contact-method',
    'url' => ['controller' => 'MemberContactMethods', 'action' => 'addForMember', $member->id],
    'contactMethodTypes' => $contactMethodTypes,
    'statusId' => 'contact-method-status',
    'contactMethodListId' => 'contact-methods',
    'contactMethodEmptyId' => 'contact-methods-empty',
]) ?>
