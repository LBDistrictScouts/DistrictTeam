<?php
/** @var \App\View\AppView $this */
$this->assign('title', __('Map CSV units'));
$this->Html->css('workspace', ['block' => true]);
?>
<div class="members upload workspace-page workspace-upload-page">
    <nav class="workspace-breadcrumb" aria-label="<?= __('Breadcrumb') ?>"><?= $this->Html->link(__('Start again with another CSV'), ['action' => 'upload']) ?></nav>
    <?= $this->element('Workspace/index_header', ['title' => __('Map CSV units'), 'description' => __('Save the group and section destinations for each unit in this CSV.'), 'actions' => []]) ?>
    <section class="workspace-upload-panel workspace-mapping-panel" aria-labelledby="unit-mapping-title">
        <div class="workspace-panel-intro"><p class="workspace-upload-kicker"><?= __('Step 2 of 4') ?></p><h2 id="unit-mapping-title"><?= __('Map CSV units') ?></h2><p><?= __('These choices save now, independently of the member import that follows.') ?></p></div>
        <?= $this->Form->create(null) ?><?= $this->Form->hidden('token', ['value' => $token]) ?>
        <div id="unit-mapping-table" data-section-groups="<?= h(json_encode($sectionGroups)) ?>" class="workspace-table-panel workspace-mapping-table workspace-first-mapping-table"><div class="table-responsive"><table><thead><tr><th><?= __('CSV unit') ?></th><th><?= __('CSV parent unit') ?></th><th><?= __('Group') ?></th><th><?= __('Section') ?></th></tr></thead><tbody><?php foreach ($unitSources as $key => $source) :
            ?><?php $saved = $savedUnitMappings[$key] ?? ['group_id' => '', 'section_id' => '']; ?><tr><td><?= h($source['unit'] ?: __('No unit supplied')) ?></td><td><?= h($source['parent'] ?: __('No parent unit supplied')) ?></td><td><?= $this->Form->control('unit_mapping.' . $key . '.group_id', ['type' => 'select', 'label' => false, 'options' => $groupOptions, 'empty' => __('No group mapping'), 'default' => $saved['group_id'], 'class' => 'unit-group-select']) ?></td><td><?= $this->Form->control('unit_mapping.' . $key . '.section_id', ['type' => 'select', 'label' => false, 'options' => $sectionOptions, 'empty' => __('No section mapping'), 'default' => $saved['section_id'], 'class' => 'unit-section-select']) ?></td></tr><?php
                                                                                                                                                     endforeach; ?></tbody></table></div></div>
        <div class="workspace-form-footer workspace-import-footer"><?= $this->Form->button(__('Save unit mappings and continue')) ?><?= $this->Html->link(__('Cancel'), ['action' => 'upload'], ['class' => 'button button-outline']) ?></div>
        <?= $this->Form->end() ?>
        <?= $this->Html->script('member-csv-unit-mapping', ['defer' => true]) ?>
    </section>
    <?= $this->element('Members/csv_help') ?>
</div>
