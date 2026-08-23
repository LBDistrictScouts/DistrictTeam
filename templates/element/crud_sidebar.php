<?php
/** @var \App\View\AppView $this */

$currentController = $this->getRequest()->getParam('controller');
$navigation = [
    'Teams' => ['label' => __('Teams'), 'icon' => '◇'],
    'Roles' => ['label' => __('Roles'), 'icon' => '◎'],
    'Members' => ['label' => __('Members'), 'icon' => '●'],
    'Appointments' => ['label' => __('Appointments'), 'icon' => '↗'],
    'MemberContactMethods' => ['label' => __('Contact methods'), 'icon' => '⌁'],
];
?>
<aside class="crud-sidebar" aria-label="<?= __('Main navigation') ?>">
    <p class="crud-sidebar-label"><?= __('Manage') ?></p>
    <nav>
        <?php foreach ($navigation as $controller => $item) : ?>
            <?php $active = $currentController === $controller; ?>
            <?= $this->Html->link(
                '<span class="crud-sidebar-icon" aria-hidden="true">' . $item['icon'] . '</span>' .
                    '<span>' . $item['label'] . '</span>',
                ['controller' => $controller, 'action' => 'index'],
                [
                    'class' => 'crud-sidebar-link' . ($active ? ' is-active' : ''),
                    'escape' => false,
                    'aria-current' => $active ? 'page' : null,
                ],
            ) ?>
        <?php endforeach; ?>
    </nav>
</aside>
