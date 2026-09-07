<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\TeamTemplate;
use ArrayObject;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;

/**
 * Teams Model
 *
 * @property \Cake\ORM\Behavior\TreeBehavior $Tree
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\TeamsTable> $ParentTeam
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\TeamsTable> $SubTeams
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\RolesTable> $Roles
 * @property \Cake\ORM\Association\HasOne<\App\Model\Table\RolesTable> $TeamLead
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
        $this->getSchema()->setColumnType('template', EnumType::from(TeamTemplate::class));

        $this->belongsTo('Groups', ['foreignKey' => 'group_id']);
        $this->belongsTo('Sections', ['foreignKey' => 'section_id']);
        $this->addBehavior('CounterCache', ['Groups' => ['teams_count']]);

        $this->addBehavior('Tree', [
            'parent' => 'team_parent_id',
            'left' => 'tree_left',
            'right' => 'tree_right',
            'level' => 'tree_level',
            'cascadeCallbacks' => true,
            'recoverOrder' => ['sort_order' => 'ASC', 'id' => 'ASC'],
        ]);

        $this->belongsTo('ParentTeam', [
            'foreignKey' => 'team_parent_id',
            'className' => 'Teams',
        ]);

        $this->hasMany('SubTeams', [
            'foreignKey' => 'team_parent_id',
            'className' => 'Teams',
            'strategy' => 'select',
            'sort' => ['SubTeams.sort_order' => 'ASC', 'SubTeams.id' => 'ASC'],
        ]);

        $this->hasMany('Roles', [
            'foreignKey' => 'team_id',
            'strategy' => 'select',
        ]);

        $this->hasOne('TeamLead', [
            'className' => 'Roles',
            'foreignKey' => 'team_id',
            'conditions' => ['TeamLead.is_lead' => true],
        ]);
    }

    /**
     * Append new or reparented teams to their siblings' saved order.
     *
     * @param \Cake\Event\EventInterface $event Save event.
     * @param \Cake\Datasource\EntityInterface $entity Team being saved.
     * @param \ArrayObject $options Save options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->isNew() || $entity->isDirty('team_parent_id')) {
            $query = $this->find()->where(['team_parent_id IS' => $entity->get('team_parent_id')]);
            $maximum = $query->select(['maximum' => $query->func()->max('sort_order')])->first();
            $entity->set('sort_order', (int)$maximum?->get('maximum') + 1);
        }
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Save event.
     * @param \Cake\Datasource\EntityInterface $entity Team being saved.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->Groups->refreshRoleCounter([$entity->get('group_id'), $entity->getOriginal('group_id')]);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Delete event.
     * @param \Cake\Datasource\EntityInterface $entity Deleted team.
     * @param \ArrayObject<string, mixed> $options Delete options.
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->Groups->refreshRoleCounter([$entity->get('group_id')]);
    }

    /**
     * Return all teams, or descendants of a parent, excluding the parent itself.
     *
     * @param string|null $parentId Parent team UUID.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function reorderQuery(?string $parentId = null): SelectQuery
    {
        $query = $this->find();
        if ($parentId !== null) {
            $parent = $this->get($parentId);
            $query->where(['Teams.tree_left >' => $parent->tree_left, 'Teams.tree_right <' => $parent->tree_right]);
        }

        return $query;
    }

    /**
     * Save sibling ordering and rebuild tree coordinates without changing parents.
     *
     * @param array<mixed> $ids All team IDs in the selected branch, in display order.
     * @param string|null $parentId Limit changes to descendants of this parent.
     * @return void
     */
    public function saveOrder(array $ids, ?string $parentId = null): void
    {
        $this->getConnection()->transactional(function () use ($ids, $parentId): void {
            $existing = $this->reorderQuery($parentId)->all()->extract('id')->toList();
            if (
                count($ids) !== count($existing)
                || count(array_filter($ids, 'is_string')) !== count($ids)
                || count(array_unique($ids)) !== count($ids)
                || array_diff($ids, $existing) !== []
            ) {
                throw new InvalidArgumentException('The team list has changed. Reload the page and try again.');
            }
            foreach (array_values($ids) as $position => $id) {
                $this->updateAll(['sort_order' => $position + 1], ['id' => $id]);
            }
            if ($parentId === null) {
                $this->getBehavior('Tree')->recover();
            } else {
                // Move only scoped siblings; leave all other branches and their order intact.
                foreach (array_reverse($ids) as $id) {
                    $this->getBehavior('Tree')->moveUp($this->get($id), true);
                }
            }
        });
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

        $validator->enum('template', TeamTemplate::class)->allowEmptyString('template');

        $validator
            ->uuid('team_parent_id')
            ->allowEmptyString('team_parent_id');

        $validator->uuid('group_id')->requirePresence('group_id', 'create')->notEmptyString('group_id');
        $validator->uuid('section_id')->allowEmptyString('section_id');

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
        $rules->add($rules->isUnique(['group_id', 'team_name']), [
            'errorField' => 'team_name',
            'message' => __('This team name is already in use by this group'),
        ]);
        $rules->add($rules->isUnique(['group_id', 'template']), [
            'errorField' => 'template',
            'message' => __('This template is already in use by this group'),
            'allowMultipleNulls' => true,
        ]);
        $rules->add($rules->isUnique(['group_id', 'slug']), [
            'errorField' => 'slug',
            'message' => __('This slug is already in use by this group'),
        ]);
        $rules->add(
            fn(EntityInterface $entity): bool => $entity->get('slug') === null
                || !$this->Roles->exists([
                    'slug' => $entity->get('slug'),
                    'group_id' => $entity->get('group_id'),
                ]),
            [
                'errorField' => 'slug',
                'message' => __('This slug is already in use by a team or role in this group'),
            ],
        );

        $rules->add($rules->existsIn(['group_id'], 'Groups'), ['errorField' => 'group_id']);
        $rules->add($rules->existsIn(['section_id'], 'Sections'), ['errorField' => 'section_id']);
        $rules->add(
            fn(EntityInterface $team): bool => $team->get('section_id') === null
                || ($team->get('group_id') !== null && $this->Sections->exists([
                    'id' => $team->get('section_id'),
                    'group_id' => $team->get('group_id'),
                ])),
            'sectionBelongsToGroup',
            ['errorField' => 'section_id', 'message' => 'Choose a section belonging to the selected group.'],
        );

        return $rules;
    }
}
