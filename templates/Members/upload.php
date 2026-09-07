<?php
/** @var \App\View\AppView $this */
$this->assign('title', __('Upload members CSV'));
$this->Html->css('workspace', ['block' => true]);
?>
<div class="members upload workspace-page workspace-upload-page">
    <?= $this->element('Members/csv_steps', ['step' => 1]) ?>
    <?= $this->element('Workspace/index_header', ['title' => __('Upload members CSV'), 'description' => __('Bring membership records into your district workspace and match them to the right teams.'), 'actions' => []]) ?>
    <section class="workspace-upload-panel" aria-labelledby="upload-file-title">
        <div class="workspace-panel-intro"><p class="workspace-upload-kicker"><?= __('Step 1 of 4') ?></p><h2 id="upload-file-title"><?= __('Choose your membership CSV') ?></h2><p><?= __('You will save unit mappings first, then review role mappings before anything is imported.') ?></p></div>
        <?= $this->Form->create(null, ['type' => 'file', 'class' => 'workspace-upload-form']) ?>
        <?= $this->Form->control('csv', ['type' => 'file', 'label' => __('CSV file'), 'accept' => '.csv,text/csv', 'required' => true]) ?>
        <div class="workspace-form-footer workspace-import-footer"><?= $this->Form->button(__('Upload and map units')) ?><?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button button-outline']) ?></div>
        <?= $this->Form->end() ?>
    </section>
    <?= $this->element('Members/csv_help') ?>
</div>
