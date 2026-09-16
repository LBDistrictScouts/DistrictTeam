<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, string> $contactMethodTypes
 * @var string $modalId
 * @var string $formId
 * @var array<string, mixed>|string|null $url
 * @var string|null $urlBase
 * @var string|null $memberSelectId
 * @var string|null $contactMethodSelectId
 * @var string|null $contactMethodListId
 * @var string|null $contactMethodEmptyId
 * @var string $statusId
 */
$urlBase ??= null;
$memberSelectId ??= null;
$contactMethodSelectId ??= null;
$contactMethodListId ??= null;
$contactMethodEmptyId ??= null;
?>
<dialog id="<?= h($modalId) ?>" class="contact-method-modal" data-contact-method-modal
    data-member-select-id="<?= h($memberSelectId) ?>">
    <h2><?= __('New contact method') ?></h2>
    <?= $this->element('Members/contact_method_form', compact(
        'formId', 'url', 'urlBase', 'memberSelectId', 'contactMethodSelectId',
        'contactMethodListId', 'contactMethodEmptyId', 'contactMethodTypes', 'statusId',
    ) + ['showSubmit' => false]) ?>
    <div class="contact-method-modal-actions">
        <?= $this->Form->button(__('Add contact method'), ['form' => $formId]) ?>
        <?= $this->Form->button(__('Cancel'), [
            'type' => 'button', 'class' => 'button button-clear', 'data-close-contact-method-modal' => true,
        ]) ?>
    </div>
</dialog>
