<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/** Saved destinations for CSV unit, team and role combinations. */
class CsvRoleMappingsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setPrimaryKey('source_key');
        $this->belongsTo('Roles', ['foreignKey' => 'role_id']);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->scalar('source_key')->lengthBetween('source_key', [64, 64])
            ->requirePresence('source_key', 'create')->notEmptyString('source_key');
        $validator->uuid('role_id')->allowEmptyString('role_id');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Integrity rules.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['role_id'], 'Roles', ['allowNullableNulls' => true]));

        return $rules;
    }
}
