<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\SectionType;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class SectionsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sections');
        $this->setDisplayField('section_name');
        $this->setPrimaryKey('id');
        $this->getSchema()->setColumnType('section_type', EnumType::from(SectionType::class));

        $this->belongsTo('Groups', [
            'foreignKey' => 'group_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Teams', ['foreignKey' => 'section_id']);
        $this->hasMany('EmailGroups', ['foreignKey' => 'section_id']);
        $this->addBehavior('CounterCache', ['Groups' => ['sections_count']]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('group_id')
            ->requirePresence('group_id', 'create')
            ->notEmptyString('group_id');

        $validator
            ->integer('section_osm_id')
            ->requirePresence('section_osm_id', 'create')
            ->greaterThan('section_osm_id', 0);

        $validator
            ->scalar('section_name')
            ->maxLength('section_name', 255)
            ->requirePresence('section_name', 'create')
            ->notEmptyString('section_name');

        $validator
            ->scalar('section_type')
            ->inList('section_type', array_map(fn(SectionType $type): string => $type->value, SectionType::cases()))
            ->requirePresence('section_type', 'create')
            ->notEmptyString('section_type');

        $validator
            ->scalar('meeting_start_time')
            ->regex('meeting_start_time', '/^([01]\d|2[0-3]):[0-5]\d$/')
            ->allowEmptyString('meeting_start_time');

        $validator
            ->scalar('meeting_end_time')
            ->regex('meeting_end_time', '/^([01]\d|2[0-3]):[0-5]\d$/')
            ->allowEmptyString('meeting_end_time');

        $validator
            ->scalar('meeting_day')
            ->inList('meeting_day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
            ->allowEmptyString('meeting_day');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['group_id'], 'Groups'), ['errorField' => 'group_id']);
        $rules->add($rules->isUnique(['section_osm_id']), ['errorField' => 'section_osm_id']);
        $rules->add($rules->isUnique(['section_name']), ['errorField' => 'section_name']);
        $rules->add(
            function (EntityInterface $section): bool {
                if (
                    $section->isNew()
                    || !$section->isDirty('group_id')
                    || !is_string($section->get('id'))
                ) {
                    return true;
                }

                return !$this->EmailGroups->exists([
                    'section_id' => $section->get('id'),
                    'group_id !=' => $section->get('group_id'),
                ]);
            },
            'emailGroupScopeAllowsSectionMove',
            [
                'errorField' => 'group_id',
                'message' => __('Update or remove email group scopes before changing this section’s group.'),
            ],
        );

        return $rules;
    }
}
