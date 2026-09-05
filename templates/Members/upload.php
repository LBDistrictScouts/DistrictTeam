<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $result
 * @var array<string, array<string, mixed>> $sources
 * @var array<string, string> $roleOptions
 * @var array<string, string> $savedMappings
 * @var string $token
 */
?>
<div class="members upload content">
    <?= $this->Html->link(__('Back to members'), ['action' => 'index']) ?>
    <h3><?= __('Upload members CSV') ?></h3>
    <details class="upload-instructions">
        <summary><?= __('Instructions') ?></summary>
        <p>Upload a UTF-8 membership CSV (maximum 10 MB). Columns may appear in any order.</p>
        <p><strong>Required columns</strong></p>
        <ul>
            <li>Membership number</li>
            <li>First name</li>
            <li>Last name</li>
            <li>Start date — for example, 02 Dec 2024</li>
        </ul>
        <p><strong>Optional columns and dates</strong></p>
        <ul>
            <li>A nonblank Preferred name is used instead of First name.</li>
            <li>Include Communication email or Contact number for members without existing contacts.</li>
            <li>End date uses the same format as Start date. Omit the column to preserve an existing end date;
                leave its value blank to clear it. New appointments without an end date are open-ended.</li>
            <li>New members use their earliest start date as their join date.</li>
        </ul>
        <p><strong>Units and role mappings</strong></p>
        <ul>
            <li>Select the units to import. All their members and contacts are imported,
                including rows without mapped roles.</li>
            <li>Map source roles to existing team / role combinations to create appointments.
                The same CSV role in different teams can have different destinations.</li>
            <li>Explicit mappings and Skip choices are remembered after a successful import
                and can be changed next time. Changing source team/role columns may require mapping again.</li>
        </ul>
        <p>Members are matched by membership number. Repeat imports update matching appointments.
            Invalid rows roll back the whole import. No teams or roles are created by this upload.</p>
    </details>
    <?php if (isset($result)): ?>
        <p><?= h(__('{0} members, {1} contact methods and {2} appointments created.',
            $result['members'], $result['contacts'], $result['appointments'])) ?></p>
        <?php foreach ($result['warnings'] as $warning): ?>
            <p><?= h($warning) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($sources)): ?>
        <h4><?= __('Map CSV roles') ?></h4>
        <?php if (!$roleOptions): ?>
            <p>Create teams and roles in the app before mapping appointments.</p>
        <?php endif; ?>
        <?php
        $units = array_unique(array_column($sources, 'unit'));
        natcasesort($units);
        $selectedUnits = (array)$this->getRequest()->getData('units', []);
        ?>
        <?= $this->Form->create(null, ['id' => 'role-mapping-form']) ?>
        <fieldset id="mapping-unit-filter-controls">
            <legend>Units to import</legend>
            <p>Select the units whose members you want to import. Unselected units will not be imported.</p>
            <?= $this->Form->hidden('units', ['value' => '']) ?>
            <?php foreach ($units as $unit): ?>
                <label>
                    <input type="checkbox" name="units[]" value="<?= h('unit:' . $unit) ?>"
                        <?= in_array('unit:' . $unit, $selectedUnits, true) ? 'checked' : '' ?>>
                    <?= h($unit ?: '(No unit supplied)') ?>
                </label>
            <?php endforeach; ?>
            <p id="mapping-unit-summary" role="status" aria-live="polite"></p>
            <p>All members and contacts in selected units are imported. Unmapped roles do not create appointments.</p>
        </fieldset>
        <?= $this->Form->hidden('step', ['value' => 'import']) ?>
        <?= $this->Form->hidden('token', ['value' => $token]) ?>
        <div class="table-responsive">
            <table>
                <thead><tr>
                    <th>CSV unit / parent team</th><th>CSV team</th><th>CSV role / role type</th>
                    <th>Rows</th><th>Existing team / role</th>
                </tr></thead>
                <tbody id="mapping-rows">
                    <?php foreach ($sources as $key => $source): ?>
                        <tr data-unit="<?= h('unit:' . $source['unit']) ?>">
                            <td><?= h(implode(' / ', array_filter([$source['unit'], $source['parent']]))) ?></td>
                            <td><?= h($source['team']) ?></td>
                            <td><?= h(($source['role'] ?: '(blank)') . ' / ' . ($source['type'] ?: '(blank)')) ?></td>
                            <td><?= h($source['count']) ?></td>
                            <td><?= $this->Form->control('mapping.' . $key, [
                                'type' => 'select', 'label' => false,
                                'aria-label' => 'Destination for ' . $source['team'] . ' / ' . $source['role'],
                                'options' => ['skip' => 'Skip appointment'] + $roleOptions,
                                'empty' => 'Members and contacts only (unmapped)', 'required' => false,
                                'default' => $savedMappings[$key] ?? '',
                            ]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $this->Form->button(__('Import selected units')) ?>
        <?= $this->Form->end() ?>
        <?= $this->Html->script('member-csv-mapping', ['defer' => true]) ?>
        <h4><?= __('Replace CSV') ?></h4>
    <?php endif; ?>
    <?= $this->Form->create(null, ['type' => 'file']) ?>
    <?= $this->Form->control('csv', ['type' => 'file', 'label' => 'CSV file', 'accept' => '.csv,text/csv', 'required' => true]) ?>
    <?= $this->Form->button(__('Upload and map roles')) ?>
    <?= $this->Form->end() ?>
</div>
