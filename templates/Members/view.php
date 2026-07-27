<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var array<int, string> $contactMethodTypes
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Member'), ['action' => 'edit', $member->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Member'), ['action' => 'delete', $member->id], ['confirm' => __('Are you sure you want to delete # {0}?', $member->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Members'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Member'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="members view content">
            <h3><?= h($member->full_name) ?></h3>
            <table>
                <tr>
                    <th><?= __('First Name') ?></th>
                    <td><?= h($member->first_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Last Name') ?></th>
                    <td><?= h($member->last_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Membership Number') ?></th>
                    <td><?= h($member->membership_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Join Date') ?></th>
                    <td><?= $this->Time->format($member->join_date, 'dd-MM-YYYY') ?></td>
                </tr>
                <tr>
                    <th><?= __('Leave Date') ?></th>
                    <td><?= h($member->leave_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Active') ?></th>
                    <td><?= $member->active ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>

            <h4><?= __('Contact Methods') ?></h4>
            <table>
                <thead>
                    <tr>
                        <th><?= __('Type') ?></th>
                        <th><?= __('Contact Method') ?></th>
                    </tr>
                </thead>
                <tbody id="contact-methods">
                    <?php foreach ($member->member_contact_methods as $memberContactMethod) : ?>
                    <tr>
                        <td><?= h($memberContactMethod->contact_method_type->label()) ?></td>
                        <td><?= h($memberContactMethod->contact_method) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?= $this->Form->create(null, [
                'id' => 'add-contact-method',
                'url' => [
                    'controller' => 'MemberContactMethods',
                    'action' => 'addForMember',
                    $member->id,
                ],
            ]) ?>
            <fieldset>
                <legend><?= __('Add Contact Method') ?></legend>
                <?= $this->Form->control('contact_method_type', ['options' => $contactMethodTypes]) ?>
                <?= $this->Form->control('contact_method') ?>
            </fieldset>
            <?= $this->Form->button(__('Add Contact Method')) ?>
            <?= $this->Form->end() ?>
            <p id="contact-method-status" role="status" aria-live="polite"></p>
        </div>
    </div>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
document.addEventListener('DOMContentLoaded', function () {
document.getElementById('add-contact-method').addEventListener('submit', async function (event) {
    event.preventDefault();

    const form = event.currentTarget;
    const status = document.getElementById('contact-method-status');
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    status.textContent = '<?= h(__('Saving…')) ?>';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok) {
            const messages = Object.values(result.errors ?? {})
                .flatMap((errors) => Object.values(errors));
            throw new Error(messages.join(' ') || '<?= h(__('Unable to save the contact method.')) ?>');
        }

        const row = document.createElement('tr');
        const type = document.createElement('td');
        const method = document.createElement('td');
        type.textContent = result.contactMethod.contact_method_type;
        method.textContent = result.contactMethod.contact_method;
        row.append(type, method);
        document.getElementById('contact-methods').append(row);

        form.reset();
        status.textContent = '<?= h(__('Contact method added.')) ?>';
    } catch (error) {
        status.textContent = error.message;
    } finally {
        button.disabled = false;
    }
});
});
<?php $this->Html->scriptEnd(); ?>
