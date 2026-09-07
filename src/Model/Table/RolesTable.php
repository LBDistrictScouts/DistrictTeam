<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\RoleTemplate;
use ArrayObject;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Roles Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\TeamsTable> $Teams
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\GroupsTable> $Groups
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\AppointmentsTable> $Appointments
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\AppointmentsTable> $CurrentAppointments
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
        $this->getSchema()->setColumnType('template', EnumType::from(RoleTemplate::class));

        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Groups', ['foreignKey' => 'group_id']);
        $this->hasMany('Appointments', [
            'foreignKey' => 'role_id',
        ]);
        $this->hasMany('CurrentAppointments', [
            'className' => 'Appointments',
            'foreignKey' => 'role_id',
            'finder' => 'current',
            'strategy' => 'select',
            'sort' => ['CurrentAppointments.effective_start_date' => 'DESC'],
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

        $validator->enum('template', RoleTemplate::class)->allowEmptyString('template');

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

        $validator
            ->boolean('multi_member_role')
            ->notEmptyString('multi_member_role');

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
        $rules->add($rules->isUnique(['group_id', 'name']), [
            'errorField' => 'name',
            'message' => __('This role name is already in use by this group'),
        ]);
        $rules->add($rules->isUnique(['group_id', 'slug']), [
            'errorField' => 'slug',
            'message' => __('This slug is already in use by this group'),
        ]);
        $rules->add($rules->isUnique(['group_id', 'template']), [
            'errorField' => 'template',
            'message' => __('This template is already in use by this group'),
            'allowMultipleNulls' => true,
        ]);
        $rules->add(
            fn(EntityInterface $entity): bool => $entity->get('group_id') === null
                || !$this->Teams->exists([
                    'slug' => $entity->get('slug'),
                    'group_id' => $entity->get('group_id'),
                ]),
            [
                'errorField' => 'slug',
                'message' => __('This slug is already in use by a team or role in this group'),
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

    /**
     * Derive the role's group from its selected team before integrity rules run.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Rules event.
     * @param \Cake\Datasource\EntityInterface $entity Role being saved.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $groupId = $this->Teams->find()
            ->select(['group_id'])
            ->where(['id' => $entity->get('team_id')])
            ->first()?->get('group_id');
        $entity->set('group_id', $groupId);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Save event.
     * @param \Cake\Datasource\EntityInterface $entity Role being saved.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->refreshGroupRoleCounters([$entity->get('team_id'), $entity->getOriginal('team_id')]);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Delete event.
     * @param \Cake\Datasource\EntityInterface $entity Deleted role.
     * @param \ArrayObject<string, mixed> $options Delete options.
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->refreshGroupRoleCounters([$entity->get('team_id')]);
    }

    /**
     * @param list<string|null> $teamIds Team IDs whose group counters need refreshing.
     * @return void
     */
    private function refreshGroupRoleCounters(array $teamIds): void
    {
        $groupIds = [];
        foreach (array_unique(array_filter($teamIds)) as $teamId) {
            $groupId = $this->Teams->find()
                ->select(['group_id'])
                ->where(['id' => $teamId])
                ->first()?->group_id;
            if ($groupId !== null) {
                $groupIds[] = $groupId;
            }
        }
        $this->Teams->Groups->refreshRoleCounter($groupIds);
    }
}
