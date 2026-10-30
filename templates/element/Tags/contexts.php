<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team|null $team
 * @var \App\Model\Entity\Role|null $role
 * @var \App\Model\Entity\Appointment|null $appointment
 */
$contexts = [
    __('Team') => $team?->tags ?? [],
    __('Role') => $role?->tags ?? [],
    __('Appointment') => $appointment?->tags ?? [],
];
$hasTags = array_filter($contexts, static fn(array $tags): bool => $tags !== []) !== [];
?>
<?php if ($hasTags) : ?>
    <div class="tag-contexts" aria-label="<?= __('Tags') ?>">
        <?php foreach ($contexts as $level => $tags) : ?>
            <?php if ($tags) : ?>
                <div class="tag-context"><span><?= h($level) ?></span><?= $this->element('Tags/list', ['tags' => $tags]) ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
