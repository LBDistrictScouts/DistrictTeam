<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Tag> $tags
 */
$tags = is_iterable($tags ?? null) ? $tags : [];
?>
<?php if ($tags) : ?>
    <ul class="tag-list" aria-label="<?= __('Tags') ?>">
        <?php foreach ($tags as $tag) : ?>
            <li><?= $this->Html->link($tag->tag_label, ['controller' => 'Tags', 'action' => 'view', $tag->id], ['class' => 'user-tag']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php else : ?>
    <span class="tag-empty">—</span>
<?php endif; ?>
