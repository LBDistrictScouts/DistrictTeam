<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $filters
 * @var list<array{name: string, label: string, options: array<string, string>, empty: string}> $filterControls
 */
$hasFilters = (bool)array_filter($filters, fn(string $value): bool => $value !== '');
?>
<details
    class="workspace-index-filters"
    data-workspace-index-filters
    data-has-active-filters="<?= $hasFilters ? 'true' : 'false' ?>"
    <?= $hasFilters ? 'open' : '' ?>
>
    <summary><?= __('Search & filters') ?></summary>
    <div class="workspace-index-filters-body">
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'workspace-index-filter-form']) ?>
        <div class="workspace-index-filter-fields">
            <div class="workspace-index-search">
                <?= $this->Form->control('q', [
                    'label' => __('Search'),
                    'type' => 'search',
                    'value' => $filters['q'] ?? '',
                    'placeholder' => __('Search this directory'),
                ]) ?>
            </div>
            <?php foreach ($filterControls as $control) : ?>
                <div class="workspace-index-filter-control">
                    <?= $this->Form->control($control['name'], [
                        'label' => $control['label'],
                        'options' => $control['options'],
                        'empty' => $control['empty'],
                        'value' => $filters[$control['name']] ?? '',
                    ]) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="workspace-index-filter-actions">
            <?= $this->Form->button(__('Apply filters')) ?>
            <?= $this->Html->link(__('Clear'), ['action' => 'index'], ['class' => 'button button-outline']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</details>
<?= $this->Html->script('workspace-index-filters', ['block' => true, 'defer' => true]) ?>
