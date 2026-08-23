<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 */
?>
<div class="row">
    <div class="column">
        <div class="teams view content">
            <h3><?= h($team->team_name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= h($team->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Team Name') ?></th>
                    <td><?= h($team->team_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Parent Team') ?></th>
                    <td><?= $team->hasValue('parent_team') ? $this->Html->link($team->parent_team->team_name, ['controller' => 'Teams', 'action' => 'view', $team->parent_team->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Team Lead Role') ?></th>
                    <td><?= $team->hasValue('team_lead') ? $this->Html->link($team->team_lead->name, ['controller' => 'Roles', 'action' => 'view', $team->team_lead->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Tree Left') ?></th>
                    <td><?= $team->tree_left === null ? '' : $this->Number->format($team->tree_left) ?></td>
                </tr>
                <tr>
                    <th><?= __('Tree Right') ?></th>
                    <td><?= $team->tree_right === null ? '' : $this->Number->format($team->tree_right) ?></td>
                </tr>
                <tr>
                    <th><?= __('Tree Level') ?></th>
                    <td><?= $team->tree_level === null ? '' : $this->Number->format($team->tree_level) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Teams') ?></h4>
                <?php if (!empty($team->sub_teams)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Team Name') ?></th>
                            <th><?= __('Team Lead Role') ?></th>
                            <th><?= __('Tree Left') ?></th>
                            <th><?= __('Tree Right') ?></th>
                            <th><?= __('Tree Level') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($team->sub_teams as $subTeam) : ?>
                        <tr>
                            <td><?= h($subTeam->id) ?></td>
                            <td><?= h($subTeam->team_name) ?></td>
                            <td><?= $subTeam->hasValue('team_lead') ? $this->Html->link($subTeam->team_lead->name, ['controller' => 'Roles', 'action' => 'view', $subTeam->team_lead->id]) : '' ?></td>
                            <td><?= h($subTeam->tree_left) ?></td>
                            <td><?= h($subTeam->tree_right) ?></td>
                            <td><?= h($subTeam->tree_level) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Teams', 'action' => 'view', $subTeam->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Teams', 'action' => 'edit', $subTeam->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Teams', 'action' => 'delete', $subTeam->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $subTeam->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
