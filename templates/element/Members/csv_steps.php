<?php
/** @var \App\View\AppView $this */
/** @var int $step */
$steps = [
    1 => ['label' => __('Upload CSV'), 'url' => ['action' => 'upload']],
    2 => ['label' => __('Map units'), 'url' => ['action' => 'mapUnits']],
    3 => ['label' => __('Map roles'), 'url' => ['action' => 'mapRoles']],
    4 => ['label' => __('Import complete'), 'url' => null],
];
?>
<nav class="workspace-breadcrumb workspace-import-steps" aria-label="<?= __('CSV import steps') ?>">
    <ol>
        <?php foreach ($steps as $number => $item) : ?>
            <li><?= $number < $step && $step < 4
                ? $this->Html->link($item['label'], $item['url'])
                : ($number === $step
                    ? $this->Html->tag('span', $item['label'], ['aria-current' => 'step'])
                    : $this->Html->tag('span', $item['label'], ['aria-disabled' => 'true'])) ?></li>
        <?php endforeach; ?>
    </ol>
</nav>
