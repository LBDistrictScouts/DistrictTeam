<?php
/**
 * @var \App\View\AppView $this
 * @var string $title
 * @var string $description
 * @var array<array{label: string, url: array, secondary?: bool, method?: string, confirm?: string}> $actions
 */
$this->assign('title', $title);
$this->Html->css('workspace', ['block' => true]);
?>
<header class="workspace-hero">
    <div>
        <p class="workspace-eyebrow"><?= __('District team workspace') ?></p>
        <h1><?= h($title) ?></h1>
        <p class="workspace-description"><?= h($description) ?></p>
    </div>
    <?php if ($actions) : ?>
    <nav class="workspace-actions" aria-label="<?= h(__('{0} actions', $title)) ?>">
        <?php foreach ($actions as $action) : ?>
            <?php
            $class = !empty($action['secondary']) ? 'workspace-secondary' : 'workspace-primary';
            if (($action['method'] ?? null) === 'post') {
                echo $this->Form->postLink($action['label'], $action['url'], [
                    'class' => $class,
                    'confirm' => $action['confirm'] ?? null,
                ]);
            } else {
                echo $this->Html->link($action['label'], $action['url'], ['class' => $class]);
            }
            ?>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
</header>
