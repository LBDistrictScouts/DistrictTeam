<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\Member $member */
/** @var array<int, string> $contactMethodTypes */
$this->assign('title', $member->full_name);
$this->Html->css('member-view', ['block' => true]);
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
                <h2 id="member-contact-heading"><?= __('Contact methods') ?></h2>
                <?php if ($member->member_contact_methods) : ?><ul class="member-contact-list" id="contact-methods"><?php foreach ($member->member_contact_methods as $memberContactMethod) : ?><li><span><?= h($memberContactMethod->contact_method_type->label()) ?></span><strong><?= h($memberContactMethod->contact_method) ?></strong><?= $this->Form->postLink($deleteContactMethodIcon, ['controller' => 'MemberContactMethods', 'action' => 'deleteForMember', $member->id, $memberContactMethod->id], ['class' => 'member-contact-delete', 'escape' => false, 'title' => __('Delete contact method'), 'aria-label' => __('Delete {0}', $memberContactMethod->contact_method), 'confirm' => __('Are you sure you want to delete this contact method?')]) ?></li><?php endforeach; ?></ul><?php else : ?><p class="member-empty" id="contact-methods-empty"><?= __('No contact methods have been added yet.') ?></p><?php endif; ?>
            </section>
            <section class="member-panel member-add-contact-panel" aria-labelledby="add-contact-heading">
                <h2 id="add-contact-heading"><?= __('Add contact method') ?></h2>
                <?= $this->Form->create(null, ['id' => 'add-contact-method', 'url' => ['controller' => 'MemberContactMethods', 'action' => 'addForMember', $member->id]]) ?>
                <fieldset><?= $this->Form->control('contact_method_type', ['options' => $contactMethodTypes]) ?><?= $this->Form->control('contact_method') ?></fieldset>
                <?= $this->Form->button(__('Add contact method')) ?><?= $this->Form->end() ?>
                <p id="contact-method-status" role="status" aria-live="polite"></p>
            </section>
        </div>
        <aside class="member-context-panels" aria-label="<?= __('Member details') ?>">
            <section class="member-panel"><h2><?= __('Membership details') ?></h2><dl class="member-metadata"><dt><?= __('Membership number') ?></dt><dd><?= h($member->membership_number) ?></dd><dt><?= __('Joined') ?></dt><dd><?= h($joinDate) ?></dd><dt><?= __('Left') ?></dt><dd><?= $member->leave_date ? h($this->Time->format($member->leave_date, 'd MMMM yyyy')) : __('Still a member') ?></dd></dl></section>
            <section class="member-panel"><details class="member-record-details"><summary><?= __('Member record details') ?></summary><dl class="member-metadata"><dt><?= __('Member UUID') ?></dt><dd><code><?= h($member->id) ?></code></dd></dl></details></section>
        </aside>
    </div>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
document.addEventListener('DOMContentLoaded', function () {
const form = document.getElementById('add-contact-method');
form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const status = document.getElementById('contact-method-status');
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true; status.textContent = '<?= h(__('Saving…')) ?>';
    try {
        const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
        const result = await response.json();
        if (!response.ok) { const messages = Object.values(result.errors ?? {}).flatMap((errors) => Object.values(errors)); throw new Error(messages.join(' ') || '<?= h(__('Unable to save the contact method.')) ?>'); }
        const row = document.createElement('li'); const type = document.createElement('span'); const method = document.createElement('strong');
        type.textContent = result.contactMethod.contact_method_type; method.textContent = result.contactMethod.contact_method; row.append(type, method);
        let list = document.getElementById('contact-methods');
        if (!list) { list = document.createElement('ul'); list.id = 'contact-methods'; list.className = 'member-contact-list'; document.getElementById('contact-methods-empty').replaceWith(list); }
        list.append(row); form.reset(); status.textContent = '<?= h(__('Contact method added.')) ?>';
    } catch (error) { status.textContent = error.message; } finally { button.disabled = false; }
});
});
<?php $this->Html->scriptEnd(); ?>
