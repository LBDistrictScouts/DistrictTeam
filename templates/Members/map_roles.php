<?php
/** @var \App\View\AppView $this */
$this->assign('title', __('Map CSV roles'));
$this->Html->css('workspace', ['block' => true]);
?>
<div class="members upload workspace-page workspace-upload-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>"><?= $this->Html->link(__('Back to unit mappings'), ['action' => 'mapUnits']) ?></nav>
    <?= $this->element('Workspace/index_header', ['title' => __('Map CSV roles'), 'description' => __('Choose the members to import and map their source roles to existing appointments.'), 'actions' => []]) ?>
    <?php if (isset($result)) : ?>
    <section class="workspace-upload-result" aria-labelledby="import-result-title"><div class="workspace-upload-result-icon" aria-hidden="true">✓</div><div><p class="workspace-upload-kicker"><?= __('Step 4 of 4 · Import complete') ?></p><h2 id="import-result-title"><?= h(__('{0} members, {1} contact methods and {2} appointments created.', $result['members'], $result['contacts'], $result['appointments'])) ?></h2><?php foreach ($result['warnings'] as $warning) :
        ?><p class="workspace-upload-warning"><?= h($warning) ?></p><?php
                                                                                                                                                                                               endforeach; ?></div></section>
    <section class="workspace-upload-panel workspace-role-results" aria-labelledby="role-results-title"><div class="workspace-panel-intro"><p class="workspace-upload-kicker"><?= __('Role import results') ?></p><h2 id="role-results-title"><?= __('Successful and failed role rows') ?></h2><p><?= __('Each source role row attempted in this import is shown below.') ?></p></div><div class="workspace-table-panel workspace-mapping-table"><div class="table-responsive"><table><thead><tr><th><?= __('CSV unit / parent team') ?></th><th><?= __('CSV team') ?></th><th><?= __('CSV role / role type') ?></th><th><?= __('Rows') ?></th><th><?= __('Result') ?></th></tr></thead><tbody><?php foreach ($roleResults as $roleResult) : ?><tr><td><?= h(implode(' / ', array_filter([$roleResult['unit'], $roleResult['parent']]))) ?></td><td><?= h($roleResult['team']) ?></td><td><?= h(($roleResult['role'] ?: __('blank')) . ' / ' . ($roleResult['type'] ?: __('blank'))) ?></td><td><span class="workspace-row-count"><?= h($roleResult['count']) ?></span></td><td><span class="workspace-role-result workspace-role-result-<?= h($roleResult['status']) ?>"><?= $roleResult['status'] === 'successful' ? __('Successful') : __('Failed') ?></span><span class="workspace-role-result-detail"><?= h(__($roleResult['detail'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div></div><div class="workspace-form-footer workspace-import-footer"><?= $this->Html->link(__('Back to members'), ['action' => 'index'], ['class' => 'button']) ?><?= $this->Html->link(__('Upload another CSV'), ['action' => 'upload'], ['class' => 'button button-outline']) ?></div></section>
    <?php else : ?>
    <section class="workspace-upload-panel workspace-mapping-panel" aria-labelledby="role-mapping-title">
        <div class="workspace-panel-intro"><p class="workspace-upload-kicker"><?= __('Step 3 of 4') ?></p><h2 id="role-mapping-title"><?= __('Map CSV roles') ?></h2><p><?= __('Unit mappings are already saved. Review role mappings before importing members.') ?></p></div>
        <?php if (!array_filter($roleOptions)) :
            ?><p class="workspace-upload-notice"><?= __('Create teams and roles in the app before mapping appointments.') ?></p><?php
        endif; ?>
        <?php $units = array_unique(array_column($sources, 'unit'));
        natcasesort($units); ?>
        <?= $this->Form->create(null, ['id' => 'role-mapping-form']) ?><?= $this->Form->hidden('token', ['value' => $token]) ?>
        <fieldset id="mapping-unit-filter-controls" class="workspace-unit-filter"><legend><?= __('Units to import') ?></legend><p><?= __('All members and contacts in selected units will be imported. Unmapped roles do not create appointments.') ?></p><div class="workspace-unit-actions"><button type="button" class="button button-outline" data-unit-selection="all"><?= __('Select all') ?></button><button type="button" class="button button-clear" data-unit-selection="none"><?= __('Unselect all') ?></button></div><?= $this->Form->hidden('units', ['value' => '']) ?><div class="workspace-unit-options"><?php foreach ($units as $unit) :
            ?><label><input type="checkbox" name="units[]" value="<?= h('unit:' . $unit) ?>" checked><span><?= h($unit ?: __('No unit supplied')) ?></span></label><?php
                                                                                          endforeach; ?></div><p id="mapping-unit-summary" class="workspace-unit-summary" role="status" aria-live="polite"></p></fieldset>
        <div class="workspace-mapping-heading workspace-role-heading"><div><p class="workspace-upload-kicker"><?= __('Role mapping') ?></p><h3><?= __('Map CSV roles') ?></h3></div><p><?= __('Connect source roles to existing team and role combinations.') ?></p></div>
        <div class="workspace-table-panel workspace-mapping-table"><div class="table-responsive"><table><thead><tr><th><?= __('CSV unit / parent team') ?></th><th><?= __('CSV team') ?></th><th><?= __('CSV role / role type') ?></th><th><?= __('Rows') ?></th><th><?= __('Existing team / role') ?></th></tr></thead><tbody id="mapping-rows"><?php foreach ($sources as $key => $source) :
            ?><tr data-unit="<?= h('unit:' . $source['unit']) ?>"><td><?= h(implode(' / ', array_filter([$source['unit'], $source['parent']]))) ?></td><td><?= h($source['team']) ?></td><td><?= h(($source['role'] ?: __('blank')) . ' / ' . ($source['type'] ?: __('blank'))) ?></td><td><span class="workspace-row-count"><?= h($source['count']) ?></span></td><td><?= $this->Form->control('mapping.' . $key, ['type' => 'select', 'label' => false, 'aria-label' => __('Destination for {0} / {1}', $source['team'], $source['role']), 'options' => ['skip' => __('Skip appointment')] + ($roleOptions[$key] ?? []), 'empty' => __('Members and contacts only (unmapped)'), 'default' => $savedMappings[$key] ?? '']) ?></td></tr><?php
                                                                                                                       endforeach; ?></tbody></table></div></div>
        <div class="workspace-form-footer workspace-import-footer"><?= $this->Form->button(__('Import selected units')) ?><?= $this->Html->link(__('Start again with another CSV'), ['action' => 'upload'], ['class' => 'button button-outline']) ?></div>
        <?= $this->Form->end() ?><?= $this->Html->script('member-csv-mapping', ['defer' => true]) ?>
    </section>
    <?php endif; ?>
</div>
