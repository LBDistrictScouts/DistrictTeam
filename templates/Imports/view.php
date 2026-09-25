<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\ImportFile $import */
/** @var array{members: iterable, appointments: iterable, roles: iterable} $missing */
/** @var iterable<\App\Model\Entity\ImportRecord> $records */
/** @var string $search */
$this->assign('title', $import->filename);
$this->Html->css('import-audit', ['block' => true]);
$members = iterator_to_array($missing['members']);
$appointments = iterator_to_array($missing['appointments']);
$roles = iterator_to_array($missing['roles']);
?>
<div class="import-audit">
    <nav class="import-audit-breadcrumb" aria-label="<?= __('Breadcrumb') ?>">
        <?= $this->Html->link(__('Imports'), ['action' => 'index']) ?> <span aria-hidden="true">/</span> <span aria-current="page"><?= h($import->filename) ?></span>
    </nav>
    <header class="import-audit-hero"><p class="import-audit-eyebrow"><?= __('Membership CSV import') ?></p><h1><?= h($import->filename) ?></h1><p><?= __('Imported {0}', $this->Time->format($import->imported_at, 'd MMMM yyyy, HH:mm')) ?></p></header>
    <section class="import-audit-stats" aria-label="<?= __('Import summary') ?>">
        <article><strong><?= $this->Number->format($import->source_record_count) ?></strong><span><?= __('Source rows') ?></span></article><article><strong><?= $this->Number->format($import->record_count) ?></strong><span><?= __('Rows processed') ?></span></article><article><strong><?= $this->Number->format($import->member_count) ?></strong><span><?= __('Members created') ?></span></article><article><strong><?= $this->Number->format($import->appointment_count) ?></strong><span><?= __('Appointments created') ?></span></article>
    </section>
    <section class="import-audit-panel" aria-labelledby="coverage-heading">
        <div class="import-audit-panel-heading"><div><p class="import-audit-eyebrow"><?= __('Coverage check') ?></p><h2 id="coverage-heading"><?= __('Not represented by this import') ?></h2></div><p><?= __('Compare the current directory with the rows selected from this CSV.') ?></p></div>
        <div class="import-coverage-grid">
            <?php foreach ([['Members', $members, 'member'], ['Appointments', $appointments, 'appointment'], ['Roles without an imported appointment', $roles, 'role']] as [$label, $entities, $type]) : ?>
            <details class="import-coverage-card"><summary><span><?= __('{0}', $label) ?></span><strong><?= $this->Number->format(count($entities)) ?></strong></summary>
                <?php if (!$entities) : ?><p><?= __('All current records are represented.') ?></p><?php else : ?><ul><?php foreach ($entities as $entity) : ?><li><?= match ($type) {
                    'member' => h($entity->full_name), 'appointment' => h($entity->member->full_name . ' — ' . $entity->role->name), default => h($entity->team->team_name . ' / ' . $entity->name),
                } ?></li><?php endforeach; ?></ul><?php endif; ?>
            </details>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="import-audit-panel import-records" aria-labelledby="row-audit-heading">
        <div class="import-audit-panel-heading"><div><p class="import-audit-eyebrow"><?= __('Traceability') ?></p><h2 id="row-audit-heading"><?= __('Row audit') ?></h2></div><p><?= __('Original CSV data and the saved record after import.') ?></p></div>
        <div class="import-record-tools">
            <?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'view', $import->id]]) ?>
            <?= $this->Form->control('q', ['label' => __('Search this import'), 'value' => $search, 'placeholder' => __('Name, membership number, email, role or result')]) ?>
            <?= $this->Form->button(__('Search')) ?>
            <?php if ($search !== '') : ?><?= $this->Html->link(__('Clear'), ['action' => 'view', $import->id], ['class' => 'button button-outline']) ?><?php endif; ?>
            <?= $this->Form->end() ?>
        </div>
        <div class="import-record-list"><?php foreach ($records as $record) : ?>
            <?php $appointment = $record->appointment; $member = $record->member ?? $appointment?->member; ?>
            <article class="import-record"><header><span><?= __('Row {0}', $record->source_line) ?></span><strong><?= h(ucfirst($record->entity_type)) ?></strong>
                <?php if ($record->entity_type === 'member' && $member) : ?>
                    <?= $this->Html->link($member->full_name, ['controller' => 'Members', 'action' => 'view', $member->id], ['class' => 'import-record-subject']) ?>
                    <span class="import-record-context"><?= __('Member #{0}', $member->membership_number) ?></span>
                <?php elseif ($record->entity_type === 'appointment' && $appointment) : ?>
                    <?= $this->Html->link($appointment->role?->name ?? __('Role'), ['controller' => 'Appointments', 'action' => 'view', $appointment->id], ['class' => 'import-record-subject']) ?>
                    <span class="import-record-context"><?= h('— ' . ($appointment->member?->full_name ?? __('Member'))) ?></span>
                <?php elseif ($record->entity_type === 'source') : ?>
                    <span class="import-record-context"><?= h(trim(($record->source_data['First name'] ?? '') . ' ' . ($record->source_data['Last name'] ?? '')) ?: __('Source row')) ?></span>
                <?php endif; ?>
                <span class="import-record-action import-record-action-<?= h($record->action) ?>"><?= h(ucwords(str_replace('_', ' ', $record->action))) ?></span>
                <?php if ($record->reason) : ?><span class="import-record-reason"><?= h($record->reason) ?></span><?php endif; ?>
                </header><div class="import-record-data">
                    <?php $changeData = $record->entity_data['_audit'] ?? []; ?>
                    <?php if ($changeData['dirty_fields'] ?? []) : ?><details class="import-record-changes"><summary><?= __('Changes made ({0})', count($changeData['dirty_fields'])) ?></summary><dl><?php foreach ($changeData['dirty_fields'] as $field) : ?><div><dt><?= h(ucwords(str_replace('_', ' ', $field))) ?></dt><dd><span><?= h(json_encode($changeData['original_values'][$field] ?? null, JSON_UNESCAPED_SLASHES)) ?></span><b>→</b><strong><?= h(json_encode($record->entity_data[$field] ?? null, JSON_UNESCAPED_SLASHES)) ?></strong></dd></div><?php endforeach; ?></dl></details><?php endif; ?>
                    <details><summary><?= __('Original CSV record') ?></summary><pre><?= h(json_encode($record->source_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre></details><details><summary><?= __('Saved record after import') ?></summary><pre><?= h(json_encode($record->entity_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre></details></div></article>
        <?php endforeach; ?></div>
        <div class="import-record-pagination">
            <span><?= $this->Paginator->counter(__('{{count}} audit records')) ?></span>
            <ul class="pagination"><?= $this->Paginator->prev(__('Previous')) ?><?= $this->Paginator->numbers() ?><?= $this->Paginator->next(__('Next')) ?></ul>
        </div>
    </section>
</div>
