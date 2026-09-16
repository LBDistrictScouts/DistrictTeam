<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, string> $contactMethodTypes
 * @var string $formId
 * @var array<string, mixed>|string|null $url
 * @var string|null $urlBase
 * @var string|null $memberSelectId
 * @var string|null $contactMethodSelectId
 * @var string|null $contactMethodListId
 * @var string|null $contactMethodEmptyId
 * @var string $statusId
 * @var bool $showSubmit
 * @var bool $appointmentEmailOnly
 */
$urlBase ??= null;
$memberSelectId ??= null;
$contactMethodSelectId ??= null;
$contactMethodListId ??= null;
$contactMethodEmptyId ??= null;
$showSubmit ??= true;
$appointmentEmailOnly ??= false;
?>
<?= $this->Form->create(null, [
    'id' => $formId,
    'url' => $url,
    'data-contact-method-form' => true,
    'data-contact-method-url-base' => $urlBase,
    'data-member-select-id' => $memberSelectId,
    'data-contact-method-select-id' => $contactMethodSelectId,
    'data-contact-method-list-id' => $contactMethodListId,
    'data-contact-method-empty-id' => $contactMethodEmptyId,
    'data-contact-method-status-id' => $statusId,
    'data-appointment-email-only' => $appointmentEmailOnly,
]) ?>
<fieldset>
    <?= $this->Form->control('contact_method_type', ['options' => $contactMethodTypes]) ?>
    <?= $this->Form->control('contact_method') ?>
</fieldset>
<?php if ($showSubmit) : ?>
    <?= $this->Form->button(__('Add contact method')) ?>
<?php endif; ?>
<?= $this->Form->end() ?>
<p id="<?= h($statusId) ?>" role="status" aria-live="polite"></p>
