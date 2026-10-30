<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Appointment;
use App\Model\Enum\ContactMethodType;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validation;
use Cake\Validation\Validator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tags Model
 *
 * @property \Cake\ORM\Association\BelongsToMany<\App\Model\Table\TeamsTable> $Teams
 * @property \Cake\ORM\Association\BelongsToMany<\App\Model\Table\RolesTable> $Roles
 * @property \Cake\ORM\Association\BelongsToMany<\App\Model\Table\AppointmentsTable> $Appointments
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\EmailGroupsTable> $EmailGroups
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Tag>
 */
class TagsTable extends Table
{
    /** @param array<string, mixed> $config */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tags');
        $this->setDisplayField('tag_label');
        $this->setPrimaryKey('id');

        $this->belongsTo('EmailGroups', [
            'foreignKey' => 'email_group_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsToMany('Teams', [
            'through' => 'TeamTags',
            'foreignKey' => 'tag_id',
            'targetForeignKey' => 'team_id',
            'sort' => ['Teams.team_name' => 'ASC'],
        ]);
        $this->belongsToMany('Roles', [
            'through' => 'RoleTags',
            'foreignKey' => 'tag_id',
            'targetForeignKey' => 'role_id',
            'sort' => ['Roles.name' => 'ASC'],
        ]);
        $this->belongsToMany('Appointments', [
            'through' => 'AppointmentTags',
            'foreignKey' => 'tag_id',
            'targetForeignKey' => 'appointment_id',
            'sort' => ['Appointments.effective_start_date' => 'DESC'],
        ]);
    }

    /**
     * Set the default tag validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('tag_label')
            ->maxLength('tag_label', 255)
            ->requirePresence('tag_label', 'create')
            ->notEmptyString('tag_label');

        $validator
            ->scalar('tag_code')
            ->maxLength('tag_code', 100)
            ->requirePresence('tag_code', 'create')
            ->notEmptyString('tag_code')
            ->add('tag_code', 'alphanumericHyphens', [
                'rule' => ['custom', '/^[A-Za-z0-9-]+$/'],
                'message' => 'Tag code may contain only letters, numbers, and hyphens.',
            ]);

        $validator->boolean('is_cognito_permissions_group');
        $validator->uuid('email_group_id')->allowEmptyString('email_group_id');

        return $validator;
    }

    /**
     * Set tag integrity rules.
     *
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['tag_code']), ['errorField' => 'tag_code']);
        $rules->add($rules->existsIn(['email_group_id'], 'EmailGroups'), ['errorField' => 'email_group_id']);
        $rules->add($rules->isUnique(['email_group_id'], ['allowMultipleNulls' => true]), [
            'errorField' => 'email_group_id',
            'message' => __('This email group is already linked to a tag.'),
        ]);

        return $rules;
    }

    /**
     * Find members whose current appointment is tagged at the appointment, role, or team level.
     *
     * Use with `$tags->findCurrentMembers($tagId)->all()`.
     * A member is returned once even when this tag is assigned at multiple levels.
     *
     * @param string $tagId Tag UUID.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Member>
     */
    public function findCurrentMembers(string $tagId): SelectQuery
    {
        $currentAppointments = $this->currentAppointmentsQueryForTag($tagId)
            ->select(['Appointments.member_id']);

        return $this->membersTable()->find()
            ->where(['Members.id IN' => $currentAppointments]);
    }

    /**
     * Find one current appointment per matching member, preferring the appointment
     * whose role belongs to the highest team in the hierarchy.
     *
     * @param string $tagId Tag UUID.
     * @param bool $groupEmailsOnly Include only valid group email appointments.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Appointment>
     */
    public function findCurrentAppointments(string $tagId, bool $groupEmailsOnly = false): SelectQuery
    {
        $query = $this->currentAppointmentsQueryForTag($tagId)
            ->contain(['Members', 'Roles', 'MemberContactMethods'])
            ->innerJoinWith('Roles.Teams')
            ->orderByAsc('Teams.tree_level')
            ->orderByDesc('Appointments.effective_start_date')
            ->orderByAsc('Appointments.id');

        if ($groupEmailsOnly) {
            $query->innerJoinWith('MemberContactMethods')
                ->where([
                    'MemberContactMethods.is_non_group_email' => false,
                    'MemberContactMethods.contact_method_type IN' => [
                        ContactMethodType::Email,
                        ContactMethodType::EmailAlias,
                        ContactMethodType::EmailGroup,
                    ],
                ]);
        }

        return $query->formatResults(static function (CollectionInterface $appointments): CollectionInterface {
            $seenMemberIds = [];
            $uniqueAppointments = [];
            foreach ($appointments as $appointment) {
                if (!$appointment instanceof Appointment) {
                    continue;
                }
                if (isset($seenMemberIds[$appointment->member_id])) {
                    continue;
                }

                $seenMemberIds[$appointment->member_id] = true;
                $uniqueAppointments[] = $appointment;
            }

            return new Collection($uniqueAppointments);
        });
    }

    /**
     * Build the shared current-appointment query for the three tag assignment levels.
     *
     * @param string $tagId Tag UUID.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Appointment>
     */
    private function currentAppointmentsQueryForTag(string $tagId): SelectQuery
    {
        if (!Validation::uuid($tagId)) {
            throw new InvalidArgumentException('A valid tag UUID is required.');
        }

        $tableLocator = TableRegistry::getTableLocator();
        $appointmentTagIds = $tableLocator->get('AppointmentTags')->find()
            ->select(['appointment_id'])
            ->where(['tag_id' => $tagId]);
        $roleTagIds = $tableLocator->get('RoleTags')->find()
            ->select(['role_id'])
            ->where(['tag_id' => $tagId]);
        $teamTagIds = $tableLocator->get('TeamTags')->find()
            ->select(['team_id'])
            ->where(['tag_id' => $tagId]);
        $rolesForTaggedTeams = $tableLocator->get('Roles')->find()
            ->select(['id'])
            ->where(['team_id IN' => $teamTagIds]);

        return $this->appointmentsTable()->find('current')
            ->where(['OR' => [
                ['Appointments.id IN' => $appointmentTagIds],
                ['Appointments.role_id IN' => $roleTagIds],
                ['Appointments.role_id IN' => $rolesForTaggedTeams],
            ]]);
    }

    /**
     * Return the configured members table.
     *
     * @return \App\Model\Table\MembersTable
     */
    private function membersTable(): MembersTable
    {
        $table = TableRegistry::getTableLocator()->get('Members');
        if (!$table instanceof MembersTable) {
            throw new RuntimeException('Expected the Members table');
        }

        return $table;
    }

    /**
     * Return the configured appointments table.
     *
     * @return \App\Model\Table\AppointmentsTable
     */
    private function appointmentsTable(): AppointmentsTable
    {
        $table = TableRegistry::getTableLocator()->get('Appointments');
        if (!$table instanceof AppointmentsTable) {
            throw new RuntimeException('Expected the Appointments table');
        }

        return $table;
    }
}
