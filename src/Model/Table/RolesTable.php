<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Roles Model
 *
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams
 * @property \App\Model\Table\AppointmentsTable&\Cake\ORM\Association\HasOne $CurrentAppointment
 * @method \App\Model\Entity\Role newEmptyEntity()
 * @method \App\Model\Entity\Role newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Role> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Role get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Role findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Role patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Role> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Role|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Role saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role> deleteManyOrFail(iterable $entities, array $options = [])
 */
class RolesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
            'joinType' => 'INNER',
        ]);
        $this->hasOne('CurrentAppointment', [
            'className' => 'Appointments',
            'foreignKey' => 'role_id',
            'finder' => 'current',
            'sort' => ['CurrentAppointment.effective_start_date' => 'DESC'],
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('team_id')
            ->notEmptyString('team_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 255)
            ->notEmptyString('slug');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        $validator
            ->boolean('currently_filled')
            ->notEmptyString('currently_filled');

        $validator
            ->boolean('is_lead')
            ->notEmptyString('is_lead');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['team_id'], 'Teams'), ['errorField' => 'team_id']);
        $rules->add($rules->isUnique(['slug']), [
            'errorField' => 'slug',
            'message' => __('This slug is already in use by a team or role'),
        ]);
        $rules->add(
            fn(EntityInterface $entity): bool => !$this->Teams->exists([
                'slug' => $entity->get('slug'),
            ]),
            [
                'errorField' => 'slug',
                'message' => __('This slug is already in use by a team or role'),
            ],
        );
        $rules->add(
            function (EntityInterface $entity): bool {
                if (!$entity->get('is_lead')) {
                    return true;
                }

                $conditions = [
                    'team_id' => $entity->get('team_id'),
                    'is_lead' => true,
                ];
                if (!$entity->isNew()) {
                    $conditions['id !='] = $entity->get('id');
                }

                return !$this->exists($conditions);
            },
            [
                'errorField' => 'is_lead',
                'message' => __('This team already has a lead role'),
            ],
        );

        return $rules;
    }
}
