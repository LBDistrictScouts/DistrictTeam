<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Appointments Model
 *
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 * @property \App\Model\Table\MemberContactMethodsTable&\Cake\ORM\Association\BelongsTo $MemberContactMethods
 * @method \App\Model\Entity\Appointment newEmptyEntity()
 * @method \App\Model\Entity\Appointment newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Appointment> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Appointment get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Appointment findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Appointment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Appointment> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Appointment|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Appointment saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Appointment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Appointment>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Appointment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Appointment> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Appointment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Appointment>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Appointment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Appointment> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AppointmentsTable extends Table
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

        $this->setTable('appointments');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('MemberContactMethods', [
            'foreignKey' => 'member_contact_method_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Synchronize role occupancy after an appointment is saved.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event After save event.
     * @param \Cake\Datasource\EntityInterface $entity Saved appointment.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function afterSave(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $roleIds = [$entity->get('role_id')];
        if ($entity->isDirty('role_id')) {
            $roleIds[] = $entity->getOriginal('role_id');
        }

        foreach (array_unique(array_filter($roleIds)) as $roleId) {
            $this->synchronizeRoleOccupancy($roleId);
        }
    }

    /**
     * Synchronize role occupancy after an appointment is deleted.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event After delete event.
     * @param \Cake\Datasource\EntityInterface $entity Deleted appointment.
     * @param \ArrayObject<string, mixed> $options Delete options.
     * @return void
     */
    public function afterDelete(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $this->synchronizeRoleOccupancy($entity->get('role_id'));
    }

    /**
     * Set whether a role has at least one currently effective appointment.
     *
     * @param string $roleId Role id.
     * @return void
     */
    private function synchronizeRoleOccupancy(string $roleId): void
    {
        $today = Date::today();
        $currentlyFilled = $this->exists([
            'role_id' => $roleId,
            'active' => true,
            'effective_start_date <=' => $today,
            'OR' => [
                'effective_end_date IS' => null,
                'effective_end_date >=' => $today,
            ],
        ]);

        $this->Roles->updateAll(
            ['currently_filled' => $currentlyFilled],
            ['id' => $roleId],
        );
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
            ->uuid('role_id')
            ->notEmptyString('role_id');

        $validator
            ->uuid('member_id')
            ->notEmptyString('member_id');

        $validator
            ->uuid('member_contact_method_id')
            ->notEmptyString('member_contact_method_id');

        $validator
            ->date('effective_start_date')
            ->requirePresence('effective_start_date', 'create')
            ->notEmptyDate('effective_start_date');

        $validator
            ->date('effective_end_date')
            ->allowEmptyDate('effective_end_date');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

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
        $rules->add(
            $rules->isUnique(['role_id', 'member_id', 'effective_start_date']),
            [
                'errorField' => 'role_id',
                'message' => __('This combination of role_id, member_id and effective_start_date already exists'),
            ],
        );
        $rules->add($rules->existsIn(['role_id'], 'Roles'), ['errorField' => 'role_id']);
        $rules->add($rules->existsIn(['member_id'], 'Members'), ['errorField' => 'member_id']);
        $rules->add(
            $rules->existsIn(['member_contact_method_id'], 'MemberContactMethods'),
            ['errorField' => 'member_contact_method_id'],
        );

        return $rules;
    }
}
