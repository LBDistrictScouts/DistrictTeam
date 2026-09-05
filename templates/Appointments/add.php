<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Appointment $appointment
 * @var \Cake\Collection\CollectionInterface|array<string> $roles
 * @var \Cake\Collection\CollectionInterface|array<string> $members
 * @var array<array<string, string>> $memberContactMethods
 * @var array<int, string> $contactMethodTypes
 */
?>
<div class="row">
    <div class="column">
        <div class="appointments form content">
            <?= $this->Form->create($appointment, ['id' => 'appointment-form']) ?>
            <fieldset>
                <legend><?= __('Add Appointment') ?></legend>
                <?php
                    echo $this->Form->control('role_id', ['options' => $roles]);
                    echo $this->Form->control('member_id', ['options' => $members]);
                    echo $this->Form->button(__('New Member'), [
                        'type' => 'button',
                        'id' => 'open-member-modal',
                        'class' => 'button button-outline',
                    ]);
                    echo $this->Form->control('member_contact_method_id', ['options' => $memberContactMethods]);
                    echo $this->Form->control('effective_start_date');
                    echo $this->Form->control('effective_end_date', ['empty' => true]);
                    echo $this->Form->control('active');
                    ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>

            <dialog id="member-modal">
                <?= $this->Form->create(null, [
                    'id' => 'add-member-form',
                    'url' => ['action' => 'addMember'],
                ]) ?>
                <fieldset>
                    <legend><?= __('Create Member') ?></legend>
                    <?= $this->Form->control('first_name') ?>
                    <?= $this->Form->control('last_name') ?>
                    <?= $this->Form->control('membership_number') ?>
                    <?= $this->Form->control('join_date', [
                        'type' => 'date',
                        'value' => date('Y-m-d'),
                    ]) ?>
                    <?= $this->Form->control('contact_method_type', [
                        'options' => $contactMethodTypes,
                    ]) ?>
                    <?= $this->Form->control('contact_method') ?>
                </fieldset>
                <p id="member-modal-status" role="status" aria-live="polite"></p>
                <?= $this->Form->button(__('Create and Select')) ?>
                <?= $this->Form->button(__('Cancel'), [
                    'type' => 'button',
                    'id' => 'close-member-modal',
                    'class' => 'button button-clear',
                ]) ?>
                <?= $this->Form->end() ?>
            </dialog>
        </div>
    </div>
</div>

<?php $this->Html->scriptStart(['block' => true]); ?>
document.addEventListener('DOMContentLoaded', function () {
    const dialog = document.getElementById('member-modal');
    const form = document.getElementById('add-member-form');
    const status = document.getElementById('member-modal-status');
    const memberSelect = document.getElementById('member-id');
    const contactMethodSelect = document.getElementById('member-contact-method-id');
    const contactMethods = Array.from(contactMethodSelect.options);

    const updateContactMethods = function () {
        const selectedId = contactMethodSelect.value;
        const options = contactMethods.filter(function (option) {
            return option.dataset.memberId === memberSelect.value;
        });
        contactMethodSelect.replaceChildren(...options);
        if (options.some(option => option.value === selectedId)) {
            contactMethodSelect.value = selectedId;
        }
        if (options.length === 0) {
            contactMethodSelect.add(new Option('<?= h(__('No contact methods available')) ?>', ''));
        }
    };

    memberSelect.addEventListener('change', updateContactMethods);
    updateContactMethods();

    document.getElementById('open-member-modal').addEventListener('click', function () {
        status.textContent = '';
        dialog.showModal();
    });
    document.getElementById('close-member-modal').addEventListener('click', function () {
        dialog.close();
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

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
                const messages = [];
                const collectMessages = function (errors) {
                    Object.values(errors).forEach(function (error) {
                        if (typeof error === 'string') {
                            messages.push(error);
                        } else {
                            collectMessages(error);
                        }
                    });
                };
                collectMessages(result.errors ?? {});
                throw new Error(
                    messages.join(' ') || '<?= h(__('Unable to create the member.')) ?>',
                );
            }

            memberSelect.add(new Option(
                result.member.full_name,
                result.member.id,
                true,
                true,
            ));

            const contactOption = new Option(
                result.contactMethod.contact_method,
                result.contactMethod.id,
                true,
                true,
            );
            contactOption.dataset.memberId = result.member.id;
            contactMethods.push(contactOption);
            updateContactMethods();
            contactMethodSelect.value = result.contactMethod.id;

            form.reset();
            dialog.close();
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });
});
<?php $this->Html->scriptEnd(); ?>
