<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Team> $teams
 */
?>
<ul class="team-order-list"<?= isset($listId) ? ' id="' . h($listId) . '"' : '' ?>>
    <?php foreach ($teams as $team): ?>
    <li class="team-order-item" data-name="<?= h($team->team_name) ?>">
        <div class="team-order-row">
            <button type="button" class="team-drag-handle"
                aria-label="<?= h(__('Drag {0} to reorder; or use the up and down arrow keys', $team->team_name)) ?>"
                title="<?= h(__('Drag to reorder')) ?>">⠿</button>
            <?php if (!empty($team->children)): ?>
            <button type="button" class="team-collapse" aria-expanded="true"
                aria-controls="team-children-<?= h($team->id) ?>"
                aria-label="<?= h(__('Collapse {0}', $team->team_name)) ?>">▾</button>
            <?php else: ?>
            <span class="team-collapse-spacer" aria-hidden="true"></span>
            <?php endif; ?>
            <span class="team-order-name"><?= h($team->team_name) ?></span>
            <input type="hidden" name="order[]" value="<?= h($team->id) ?>">
            <button type="button" class="team-move" data-direction="up"
                aria-label="<?= h(__('Move {0} up', $team->team_name)) ?>">↑</button>
            <button type="button" class="team-move" data-direction="down"
                aria-label="<?= h(__('Move {0} down', $team->team_name)) ?>">↓</button>
        </div>
        <?php if (!empty($team->children)): ?>
            <?= $this->element('Teams/order_list', ['teams' => $team->children, 'listId' => 'team-children-' . $team->id]) ?>
        <?php endif; ?>
    </li>
    <?php endforeach; ?>
</ul>
