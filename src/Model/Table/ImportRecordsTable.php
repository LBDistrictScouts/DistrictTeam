<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/** Source rows and resulting entity snapshots for an import file. */
class ImportRecordsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('import_records');
        $this->setPrimaryKey('id');
        $this->getSchema()->setColumnType('source_data', 'json');
        $this->getSchema()->setColumnType('entity_data', 'json');
        $this->belongsTo('ImportFiles', ['foreignKey' => 'import_file_id']);
        $this->belongsTo('Members', ['foreignKey' => 'member_id']);
        $this->belongsTo('Appointments', ['foreignKey' => 'appointment_id']);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->uuid('import_file_id')->requirePresence('import_file_id', 'create')
            ->notEmptyString('import_file_id');
        $validator->nonNegativeInteger('source_line')->requirePresence('source_line', 'create');
        $validator->inList('entity_type', ['source', 'member', 'appointment'])
            ->requirePresence('entity_type', 'create');
        $validator->inList('action', ['created', 'updated', 'unchanged', 'not_imported'])
            ->requirePresence('action', 'create');
        $validator->scalar('reason')->allowEmptyString('reason');
        $validator->uuid('member_id')->allowEmptyString('member_id');
        $validator->uuid('appointment_id')->allowEmptyString('appointment_id');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Integrity rules.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['import_file_id'], 'ImportFiles'));
        $rules->add($rules->existsIn(['member_id'], 'Members', ['allowNullableNulls' => true]));
        $rules->add($rules->existsIn(['appointment_id'], 'Appointments', ['allowNullableNulls' => true]));

        return $rules;
    }
}
