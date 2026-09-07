<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Section;
use App\Model\Entity\Team;
use App\Model\Enum\GroupType;
use Cake\Core\Configure;
use Cake\I18n\Date;

class GroupsController extends AppController
{
    /**
     * Show the group report card for outstanding roles and email addresses.
     *
     * @param string|null $id Group id, or null for the district-wide overview.
     * @return void
     */
    public function reportCard(?string $id = null): void
    {
        $this->request->allowMethod(['get']);

        $group = $id === null ? null : $this->fetchTable('Groups')->get($id);

        $roles = $this->fetchTable('Roles');
        $currentAppointmentRoleIds = $this->fetchTable('Appointments')->find('current')->select(['role_id']);
        $vacantRolesQuery = $roles->find()
            ->where(['OR' => [
                [
                    'Roles.multi_member_role' => true,
                    'Roles.id NOT IN' => clone $currentAppointmentRoleIds,
                ],
                [
                    'Roles.multi_member_role' => false,
                    'Roles.id NOT IN' => clone $currentAppointmentRoleIds,
                    'OR' => [
                        'Roles.is_covered_until IS' => null,
                        'Roles.is_covered_until <' => Date::today(),
                    ],
                ],
            ]])
            ->contain(['Teams', 'Groups'])
            ->orderByAsc('Groups.sort_order')
            ->orderByAsc('Groups.group_name')
            ->orderByAsc('Teams.tree_left')
            ->orderByAsc('Roles.name');
        if ($group !== null) {
            $vacantRolesQuery->where(['Roles.group_id' => $group->get('id')]);
        }
        $vacantRoles = $vacantRolesQuery->all()->toList();

        $coveredRolesQuery = $roles->find()
            ->where([
                'Roles.id NOT IN' => clone $currentAppointmentRoleIds,
                'Roles.multi_member_role' => false,
                'Roles.is_covered_until >=' => Date::today(),
            ])
            ->contain(['Teams', 'Groups'])
            ->orderByAsc('Roles.is_covered_until')
            ->orderByAsc('Groups.sort_order')
            ->orderByAsc('Groups.group_name')
            ->orderByAsc('Teams.tree_left')
            ->orderByAsc('Roles.name');
        if ($group !== null) {
            $coveredRolesQuery->where(['Roles.group_id' => $group->get('id')]);
        }
        $coveredRoles = $coveredRolesQuery->all()->toList();

        $trusteeAppointmentsQuery = $this->fetchTable('Appointments')->find('current')
            ->select(['Appointments.id'])
            ->innerJoinWith('Roles', function ($query) {
                return $query->where(['Roles.is_trustee_role' => true]);
            });
        if ($group !== null) {
            $trusteeAppointmentsQuery->where(['Roles.group_id' => $group->get('id')]);
        }
        $trusteeAppointmentCount = $trusteeAppointmentsQuery->count();
        $trusteeBoardTarget = (int)Configure::read('TrusteeBoard.targetAppointments');
        if ($group === null) {
            $trusteeBoardCount = $this->fetchTable('Groups')->find()
                ->where(['Groups.type' => GroupType::Group->value])
                ->count();
            $trusteeBoardTarget *= $trusteeBoardCount;
        }
        $trusteeBoardRoles = [];
        $missingTrusteeRoles = [];
        $missingTrusteeMemberCount = 0;
        $showTrusteeBoardGaps = $group !== null && $trusteeAppointmentCount < $trusteeBoardTarget;
        if ($showTrusteeBoardGaps) {
            $trusteeBoardRoles = $roles->find()
                ->where([
                    'Roles.group_id' => $group->get('id'),
                    'Roles.is_trustee_role' => true,
                ])
                ->contain(['CurrentAppointments.Members'])
                ->orderByAsc('Roles.name')
                ->all()
                ->toList();
            $rolesByTemplate = [];
            foreach ($trusteeBoardRoles as $trusteeBoardRole) {
                $rolesByTemplate[$trusteeBoardRole->template->value ?? ''] = $trusteeBoardRole;
            }
            foreach (
                [
                    'group-lead-volunteer' => __('Group Lead Volunteer'),
                    'trustee-board-chair' => __('Trustee Board Chair'),
                    'group-treasurer' => __('Group Treasurer'),
                ] as $template => $roleName
            ) {
                $trusteeBoardRole = $rolesByTemplate[$template] ?? null;
                if ($trusteeBoardRole === null || $trusteeBoardRole->get('current_appointments') === []) {
                    $missingTrusteeRoles[] = $roleName;
                }
            }
            $missingTrusteeMemberCount = max(
                0,
                $trusteeBoardTarget - $trusteeAppointmentCount - count($missingTrusteeRoles),
            );
        }

        $contactMethods = $this->fetchTable('MemberContactMethods');
        $nonGroupEmailsQuery = $contactMethods->find()
            ->where(['MemberContactMethods.is_non_group_email' => true])
            ->contain(['Members'])
            ->orderByAsc('Members.last_name')
            ->orderByAsc('Members.first_name')
            ->orderByAsc('MemberContactMethods.contact_method');
        if ($group !== null) {
            $memberIds = $this->fetchTable('Appointments')->find('current')
                ->select(['Appointments.member_id'])
                ->innerJoinWith('Roles', function ($query) use ($group) {
                    return $query->where(['Roles.group_id' => $group->get('id')]);
                });
            $nonGroupEmailsQuery->where(['MemberContactMethods.member_id IN' => $memberIds]);
        }
        $nonGroupEmails = $nonGroupEmailsQuery->all()->toList();

        $this->set(compact(
            'group',
            'vacantRoles',
            'coveredRoles',
            'trusteeAppointmentCount',
            'trusteeBoardTarget',
            'trusteeBoardRoles',
            'missingTrusteeRoles',
            'missingTrusteeMemberCount',
            'showTrusteeBoardGaps',
            'nonGroupEmails',
        ));
    }

    /**
     * List imported groups.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $query = $this->fetchTable('Groups')->find();
        $filters = [
            'q' => $this->indexFilter('q'),
            'type' => $this->indexChoice('type', ['district', 'group']),
        ];
        if ($filters['q'] !== '') {
            $term = '%' . strtolower($filters['q']) . '%';
            $conditions = ['LOWER(Groups.group_name) LIKE' => $term];
            if (ctype_digit($filters['q'])) {
                $conditions['Groups.group_osm_id'] = (int)$filters['q'];
            }
            $query->where(['OR' => $conditions]);
        }
        if ($filters['type'] !== '') {
            $query->where(['Groups.type' => $filters['type']]);
        }
        $groupTypeOptions = [];
        foreach (GroupType::cases() as $type) {
            $groupTypeOptions[$type->value] = $type->label();
        }
        $filterControls = [[
            'name' => 'type', 'label' => __('Type'), 'options' => $groupTypeOptions, 'empty' => __('All types'),
        ]];
        $this->set('groups', $this->paginate($query, [
            'order' => ['Groups.sort_order' => 'ASC', 'Groups.group_name' => 'ASC'],
        ]));
        $this->set(compact('filters', 'filterControls'));
    }

    /**
     * Show a group together with its sections and teams.
     *
     * @param string|null $id Group id.
     * @return void
     */
    public function view(?string $id = null): void
    {
        $this->request->allowMethod(['get']);
        $groups = $this->fetchTable('Groups');
        $group = $groups->get($id);
        $sections = $groups->Sections->find()
            ->where(['Sections.group_id' => $group->get('id')])
            ->orderByAsc('Sections.section_name')
            ->all()
            ->toList();
        $group->sections = array_values(array_filter(
            $sections,
            static fn(mixed $section): bool => $section instanceof Section,
        ));
        $teams = $groups->Teams->find()
            ->where(['Teams.group_id' => $group->get('id')])
            ->contain(['Sections', 'Roles'])
            ->orderByAsc('Teams.tree_left')
            ->orderByAsc('Teams.team_name')
            ->all()
            ->toList();
        $group->teams = array_values(array_filter(
            $teams,
            static fn(mixed $team): bool => $team instanceof Team,
        ));
        $this->set(compact('group'));
    }
}
