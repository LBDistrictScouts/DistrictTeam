<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Teams Model
 *
 * @property \Cake\ORM\Behavior\TreeBehavior $Tree
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $ParentTeam
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\HasMany $SubTeams
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\HasMany $Roles
 * @method \App\Model\Entity\Team newEmptyEntity()
 * @method \App\Model\Entity\Team newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Team> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Team get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Team findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Team patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Team> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Team|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Team saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Team>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Team>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Team>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Team> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Team>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Team>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Team>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Team> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TeamsTable extends Table
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

        $this->setTable('teams');
        $this->setDisplayField('team_name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Tree', [
            'parent' => 'team_parent_id',
            'left' => 'tree_left',
            'right' => 'tree_right',
            'level' => 'tree_level',
            'cascadeCallbacks' => true,
        ]);

        $this->belongsTo('ParentTeam', [
            'foreignKey' => 'team_parent_id',
            'className' => 'Teams',
        ]);

        $this->hasMany('SubTeams', [
            'foreignKey' => 'team_parent_id',
            'className' => 'Teams',
            'strategy' => 'select',
        ]);

        $this->hasMany('Roles', [
            'foreignKey' => 'team_id',
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
            ->scalar('team_name')
            ->maxLength('team_name', 255)
            ->requirePresence('team_name', 'create')
            ->notEmptyString('team_name');

        $validator
            ->uuid('team_parent_id')
            ->allowEmptyString('team_parent_id');

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
        $rules->add($rules->existsIn(['team_parent_id'], 'ParentTeam'), ['errorField' => 'team_parent_id']);
        $rules->add($rules->isUnique(['slug']), [
            'errorField' => 'slug',
            'message' => __('This slug is already in use by a team or role'),
        ]);
        $rules->add(
            fn(EntityInterface $entity): bool => $entity->get('slug') === null
                || !$this->Roles->exists(['slug' => $entity->get('slug')]),
            [
                'errorField' => 'slug',
                'message' => __('This slug is already in use by a team or role'),
            ],
        );

        return $rules;
    }
}
