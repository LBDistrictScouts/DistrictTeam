<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/** Saved Group and Section destinations for CSV unit and parent-unit combinations. */
class CsvUnitMappingsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setPrimaryKey('source_key');
        $this->belongsTo('Groups', ['foreignKey' => 'group_id']);
        $this->belongsTo('Sections', ['foreignKey' => 'section_id']);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->scalar('source_key')->lengthBetween('source_key', [64, 64])
            ->requirePresence('source_key', 'create')->notEmptyString('source_key');
        $validator->uuid('group_id')->allowEmptyString('group_id');
        $validator->uuid('section_id')->allowEmptyString('section_id');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Integrity rules.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['group_id'], 'Groups', ['allowNullableNulls' => true]));
        $rules->add($rules->existsIn(['section_id'], 'Sections', ['allowNullableNulls' => true]));

        return $rules;
    }
}
