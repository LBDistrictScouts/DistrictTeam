<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\ContactMethodType;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MemberContactMethods Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\MembersTable> $Members
 * @method \App\Model\Entity\MemberContactMethod newEmptyEntity()
 * @method \App\Model\Entity\MemberContactMethod newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MemberContactMethod> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MemberContactMethod get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MemberContactMethod findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MemberContactMethod patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MemberContactMethod> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MemberContactMethod|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MemberContactMethod saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MemberContactMethod>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MemberContactMethod>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MemberContactMethod>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MemberContactMethod> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MemberContactMethod>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MemberContactMethod>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MemberContactMethod>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MemberContactMethod> deleteManyOrFail(iterable $entities, array $options = [])
 */
class MemberContactMethodsTable extends Table
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

        $this->setTable('member_contact_methods');
        $this->setDisplayField('contact_method');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'contact_method_type',
            EnumType::from(ContactMethodType::class),
        );

        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
            'joinType' => 'INNER',
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
            ->uuid('member_id')
            ->notEmptyString('member_id');

        $validator
            ->scalar('contact_method')
            ->maxLength('contact_method', 255)
            ->requirePresence('contact_method', 'create')
            ->notEmptyString('contact_method');

        $validator
            ->enum('contact_method_type', ContactMethodType::class)
            ->notEmptyString('contact_method_type');

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
            $rules->isUnique(['member_id', 'contact_method']),
            [
                'errorField' => 'member_id',
                'message' => __('This combination of member_id and contact_method already exists'),
            ],
        );
        $rules->add($rules->existsIn(['member_id'], 'Members'), ['errorField' => 'member_id']);

        return $rules;
    }
}
