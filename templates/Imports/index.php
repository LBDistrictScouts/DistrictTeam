<?php
/** @var \App\View\AppView $this */
/** @var iterable<\App\Model\Entity\ImportFile> $imports */
$this->assign('title', __('Import history'));
$this->Html->css('import-audit', ['block' => true]);
?>
<div class="imports-index workspace-page">
    <?= $this->element('Workspace/index_header', [
        'title' => __('Import history'),
        'description' => __('Review membership CSVs, their changes, and the records absent from each source.'),
        'actions' => [],
    ]) ?>
    <section class="workspace-table-panel" aria-labelledby="import-history-heading">
        <div class="workspace-table-heading">
            <h2 id="import-history-heading"><?= __('Imported files') ?></h2>
            <span><?= $this->Paginator->counter(__('{{count}} files')) ?></span>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr><th><?= __('File') ?></th><th><?= __('Imported') ?></th><th><?= __('Rows') ?></th><th><?= __('Created') ?></th><th class="actions"><?= __('Audit') ?></th></tr></thead>
                <tbody>
                <?php if (count($imports) === 0) : ?>
                    <tr><td colspan="5" class="workspace-empty"><?= __('No CSV files have been imported yet.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($imports as $import) : ?>
                    <tr>
                        <td><strong><?= h($import->filename) ?></strong></td>
                        <td><?= $this->Time->format($import->imported_at, 'd MMM yyyy, HH:mm') ?></td>
                        <td><?= __('{0} of {1}', $this->Number->format($import->record_count), $this->Number->format($import->source_record_count)) ?></td>
                        <td><?= __('{0} members · {1} appointments', $import->member_count, $import->appointment_count) ?></td>
                        <td class="actions"><?= $this->Html->link(__('View audit'), ['action' => 'view', $import->id]) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
