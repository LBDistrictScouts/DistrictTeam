<?php
/**
 * @var \App\View\AppView $this
 */
$query = $this->request->getQueryParams();
unset($query['page'], $query['limit']);
?>
<?= $this->Form->create(null, ['type' => 'get', 'class' => 'workspace-pagination-limit']) ?>
<?php foreach ($query as $name => $value) : ?>
    <?= $this->Form->hidden($name, ['value' => $value]) ?>
<?php endforeach; ?>
<?= $this->Form->control('limit', [
    'label' => __('Items per page'),
    'options' => [10 => 10, 25 => 25, 50 => 50, 75 => 75, 100 => 100],
    'value' => $this->request->getQuery('limit'),
    'onchange' => 'this.form.submit()',
]) ?>
<noscript><?= $this->Form->button(__('Apply')) ?></noscript>
<?= $this->Form->end() ?>
