<?php
/** @var \App\View\AppView $this */
/** @var string $modalId */
/** @var string $class */
$class ??= 'button button-outline';
?>
<?= $this->Form->button(__('New Contact Method'), [
    'type' => 'button', 'class' => $class, 'data-open-contact-method-modal' => $modalId,
]) ?>
