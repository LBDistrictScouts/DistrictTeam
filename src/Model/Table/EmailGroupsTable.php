<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Email Groups Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\GroupsTable> $Groups
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\TeamsTable> $Teams
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\SectionsTable> $Sections
 * @property \Cake\ORM\Association\HasMany<\App\Model\Table\MemberContactMethodsTable> $MemberContactMethods
 */
class EmailGroupsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('email_groups');
        $this->setDisplayField('email_group_name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Groups', ['foreignKey' => 'group_id', 'joinType' => 'INNER']);
        $this->belongsTo('Teams', ['foreignKey' => 'team_id']);
        $this->belongsTo('Sections', ['foreignKey' => 'section_id']);
        $this->hasMany('MemberContactMethods', ['foreignKey' => 'email_group_id']);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->uuid('group_id')->requirePresence('group_id', 'create')->notEmptyString('group_id');
        $validator->uuid('team_id')->allowEmptyString('team_id');
        $validator->uuid('section_id')->allowEmptyString('section_id');
        $validator->scalar('email_group_name')->maxLength('email_group_name', 255)
            ->requirePresence('email_group_name', 'create')->notEmptyString('email_group_name');
        $validator->email('email_address')->maxLength('email_address', 255)
            ->requirePresence('email_address', 'create')->notEmptyString('email_address');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['group_id'], 'Groups'), ['errorField' => 'group_id']);
        $rules->add(
            fn(EntityInterface $emailGroup): bool => in_array($emailGroup->get('team_id'), [null, ''], true)
                || $this->Teams->exists(['id' => $emailGroup->get('team_id')]),
            ['errorField' => 'team_id'],
        );
        $rules->add(
            fn(EntityInterface $emailGroup): bool => in_array($emailGroup->get('section_id'), [null, ''], true)
                || $this->Sections->exists(['id' => $emailGroup->get('section_id')]),
            ['errorField' => 'section_id'],
        );
        $rules->add($rules->isUnique(['group_id', 'email_group_name']), [
            'errorField' => 'email_group_name',
            'message' => __('This email group name is already in use by this group'),
        ]);
        $rules->add($rules->isUnique(['email_address']), [
            'errorField' => 'email_address',
            'message' => __('This email address is already in use'),
        ]);
        $rules->add(
            function (EntityInterface $emailGroup): bool {
                $emailAddress = $emailGroup->get('email_address');
                $groupId = $emailGroup->get('group_id');
                if (!is_string($emailAddress) || !is_string($groupId)) {
                    return true;
                }
                $group = $this->Groups->find()
                    ->select(['domains'])
                    ->where(['Groups.id' => $groupId])
                    ->first();
                if (!$group instanceof EntityInterface) {
                    return true;
                }

                $emailAddress = strtolower($emailAddress);
                foreach ($group->get('domains') ?? [] as $domain) {
                    if (is_string($domain) && str_ends_with($emailAddress, '@' . strtolower($domain))) {
                        return true;
                    }
                }

                return false;
            },
            'emailAddressUsesGroupDomain',
            [
                'errorField' => 'email_address',
                'message' => __('Use an email address with a domain configured for the selected group.'),
            ],
        );
        $rules->add(
            fn(EntityInterface $emailGroup): bool => in_array($emailGroup->get('team_id'), [null, ''], true)
                || $this->Teams->exists([
                    'id' => $emailGroup->get('team_id'),
                    'group_id' => $emailGroup->get('group_id'),
                ]),
            'teamBelongsToGroup',
            ['errorField' => 'team_id', 'message' => 'Choose a team belonging to the selected group.'],
        );
        $rules->add(
            fn(EntityInterface $emailGroup): bool => in_array($emailGroup->get('section_id'), [null, ''], true)
                || $this->Sections->exists([
                    'id' => $emailGroup->get('section_id'),
                    'group_id' => $emailGroup->get('group_id'),
                ]),
            'sectionBelongsToGroup',
            ['errorField' => 'section_id', 'message' => 'Choose a section belonging to the selected group.'],
        );

        return $rules;
    }
}
