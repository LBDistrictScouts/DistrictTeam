<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\GroupType;
use Cake\Database\Type\EnumType;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Groups Model
 *
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\TeamsTable> $Teams
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\SectionsTable> $Sections
 * @method \App\Model\Entity\Group newEmptyEntity()
 * @method \App\Model\Entity\Group newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Group> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Group get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Group findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Group patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Group> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Group|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Group saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Group>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Group>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Group>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Group> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Group>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Group>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Group>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Group> deleteManyOrFail(iterable $entities, array $options = [])
 */
class GroupsTable extends Table
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

        $this->setTable('groups');
        $this->setDisplayField('group_name');
        $this->setPrimaryKey('id');
        $this->getSchema()->setColumnType('type', EnumType::from(GroupType::class));
        $this->getSchema()->setColumnType('domains', 'json');

        $this->hasMany('Teams', [
            'foreignKey' => 'group_id',
        ]);
        $this->hasMany('Sections', [
            'foreignKey' => 'group_id',
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
            ->scalar('group_name')
            ->maxLength('group_name', 255)
            ->requirePresence('group_name', 'create')
            ->notEmptyString('group_name');

        $validator
            ->integer('group_osm_id')
            ->allowEmptyString('group_osm_id');

        $validator
            ->integer('sort_order')
            ->greaterThan('sort_order', 0)
            ->allowEmptyString('sort_order');

        $validator->enum('type', GroupType::class)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');
        $validator->requirePresence('domains', 'create')
            ->notEmptyArray('domains')
            ->add('domains', 'hostnames', [
                'rule' => function (mixed $value): bool {
                    if (!is_array($value) || !array_is_list($value) || $value === []) {
                        return false;
                    }
                    foreach ($value as $domain) {
                        if (!is_string($domain) || !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                            return false;
                        }
                    }

                    return true;
                },
                'message' => 'Provide a non-empty list of domain hostnames, without URLs or paths.',
            ]);

        return $validator;
    }

    /**
     * Recalculate the indirect role counter after a related record changes.
     *
     * @param list<string|null> $groupIds Group IDs to update.
     * @return void
     */
    public function refreshRoleCounter(array $groupIds): void
    {
        foreach (array_unique(array_filter($groupIds)) as $groupId) {
            $teamIds = $this->Teams->find()->where(['group_id' => $groupId])->all()->extract('id')->toList();
            $rolesCount = $teamIds === []
                ? 0
                : $this->Teams->Roles->find()->where(['team_id IN' => $teamIds])->count();

            $this->updateAll(['roles_count' => $rolesCount], ['id' => $groupId]);
        }
    }
}
