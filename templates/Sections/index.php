<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Section> $sections
 */
?>
<div class="sections index content workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Sections'),
        'description' => __('Find sections, their groups and when they meet.'),
        'actions' => [
        ],
    ]) ?>
    <section class="workspace-table-panel" aria-label="<?= __('Sections') ?>">
    <div class="workspace-table-heading">
        <h2><?= __('Directory') ?></h2>
        <span><?= $this->Paginator->counter(__('{{count}} records')) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('section_name', __('Section')) ?></th>
                    <th><?= $this->Paginator->sort('Groups.group_name', __('Group')) ?></th>
                    <th><?= $this->Paginator->sort('section_type', __('Type')) ?></th>
                    <th><?= $this->Paginator->sort('section_osm_id', __('OSM ID')) ?></th>
                    <th><?= $this->Paginator->sort('meeting_day', __('Meeting Day')) ?></th>
                    <th><?= $this->Paginator->sort('meeting_start_time', __('Start Time')) ?></th>
                    <th><?= $this->Paginator->sort('meeting_end_time', __('End Time')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($sections) === 0): ?>
                <tr><td colspan="7" class="workspace-empty"><?= __('No records to display yet.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($sections as $item): ?>
                <tr>
                    <td><?= h($item->section_name) ?></td>
                    <td><?= h($item->group->group_name) ?></td>
                    <td><?= h($item->section_type) ?></td>
                    <td><?= h($item->section_osm_id) ?></td>
                    <td><?= h($item->meeting_day) ?></td>
                    <td><?= h($item->meeting_start_time) ?></td>
                    <td><?= h($item->meeting_end_time) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
    </section>
</div>
