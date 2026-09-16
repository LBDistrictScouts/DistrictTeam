<?php
/** @var \App\View\AppView $this */
/** @var array<int, string> $appointmentContactMethodTypes */
?>
<?= $this->Form->button(__('New Contact Method'), [
    'type' => 'button', 'id' => 'open-contact-method-modal', 'class' => 'button button-outline',
]) ?>
<dialog id="contact-method-modal" class="appointment-contact-method-modal">
    <h2><?= __('New contact method') ?></h2>
    <?= $this->element('Members/contact_method_form', [
        'formId' => 'add-appointment-contact-method-form',
        'url' => null,
        'urlBase' => $this->Url->build('/member-contact-methods/add-for-member/'),
        'memberSelectId' => 'member-id',
        'contactMethodSelectId' => 'member-contact-method-id',
        'contactMethodTypes' => $appointmentContactMethodTypes,
        'statusId' => 'appointment-contact-method-status',
    ]) ?>
    <?= $this->Form->button(__('Cancel'), [
        'type' => 'button', 'id' => 'close-contact-method-modal', 'class' => 'button button-clear',
    ]) ?>
</dialog>
