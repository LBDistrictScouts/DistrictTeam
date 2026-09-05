<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
use App\Model\Enum\GroupType;
use App\Model\Table\TeamsTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validation;
use RuntimeException;

class TeamsController extends AppController
{
    protected string $tableAlias = 'Teams';

    private ?string $groupId = null;

    protected array $contain = [
        'Groups',
        'Sections',
        'ParentTeam.Groups',
        'ParentTeam.Sections',
        'TeamLead',
        'SubTeams.TeamLead',
        'SubTeams.Groups',
        'SubTeams.Sections',
    ];

    /**
     * Associations included in individual team responses.
     *
     * @var array<int|string, mixed>
     */
    protected array $viewContain = [
        'Groups',
        'Sections',
        'ParentTeam.Groups',
        'ParentTeam.Sections',
        'TeamLead',
        'SubTeams.TeamLead',
        'SubTeams.Groups',
        'SubTeams.Sections',
        'Roles' => [
            'fields' => [
                'Roles.id',
                'Roles.team_id',
                'Roles.name',
                'Roles.slug',
                'Roles.currently_filled',
                'Roles.is_lead',
                'Roles.multi_member_role',
            ],
        ],
        'Roles.CurrentAppointments' => [
            'fields' => [
                'CurrentAppointments.id',
                'CurrentAppointments.role_id',
                'CurrentAppointments.member_id',
            ],
        ],
        'Roles.CurrentAppointments.Members' => [
            'fields' => [
                'Members.id',
                'Members.first_name',
                'Members.last_name',
            ],
        ],
    ];

    protected array $order = ['Teams.sort_order' => 'ASC', 'Teams.id' => 'ASC'];

    /**
     * Return a group's teams using the standard team collection response.
     *
     * @param string $groupUUID Shared core-data group UUID.
     * @return void
     */
    public function groupTeams(string $groupUUID): void
    {
        $this->request->allowMethod(['get']);
        if (!Validation::uuid($groupUUID)) {
            throw new NotFoundException('Group not found.');
        }
        $group = $this->teamsTable()->Groups->get($groupUUID);
        $this->groupId = $group->id;
        parent::index();
    }

    /**
     * Return one team with its slim role listing.
     *
     * @param string $id Team UUID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $teams = $this->teamsTable();
        $team = $this->scopedQuery($this->viewContain)->where(['Teams.id' => $id])->firstOrFail();
        $roles = $team->roles;
        $subTeamIds = array_map(
            fn($subTeam): string => $subTeam->id,
            $team->sub_teams,
        );

        if ($subTeamIds !== []) {
            $subTeamLeads = $teams->Roles->find()
                ->select([
                    'Roles.id',
                    'Roles.team_id',
                    'Roles.name',
                    'Roles.slug',
                    'Roles.currently_filled',
                    'Roles.is_lead',
                    'Roles.multi_member_role',
                ])
                ->where([
                    'Roles.team_id IN' => $subTeamIds,
                    'Roles.is_lead' => true,
                ])
                ->contain([
                    'CurrentAppointments' => [
                        'fields' => [
                            'CurrentAppointments.id',
                            'CurrentAppointments.role_id',
                            'CurrentAppointments.member_id',
                        ],
                    ],
                    'CurrentAppointments.Members' => [
                        'fields' => [
                            'Members.id',
                            'Members.first_name',
                            'Members.last_name',
                        ],
                    ],
                ])
                ->all()
                ->toList();
            $roles = $this->mergeRoles($roles, $subTeamLeads);
        }

        usort($roles, fn(Role $left, Role $right): int => $left->name <=> $right->name);
        $team->set('roles', $roles);

        $this->set('data', $team);
        $this->viewBuilder()->setOption('serialize', ['data']);
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function collectionQuery(): SelectQuery
    {
        return $this->scopedQuery($this->contain);
    }

    /**
     * Scope top-level and embedded teams to the requested group, or district groups by default.
     *
     * @param array<int|string, mixed> $contain Relationships to include.
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function scopedQuery(array $contain): SelectQuery
    {
        $groups = $this->teamsTable()->Groups->find()->select(['id']);
        $groups->where($this->groupId === null
            ? ['type' => GroupType::District]
            : ['id' => $this->groupId]);

        $query = $this->teamsTable()->find()
            ->where(['Teams.group_id IN' => clone $groups])
            ->contain($contain)
            ->contain([
                'ParentTeam' => ['conditions' => ['ParentTeam.group_id IN' => clone $groups]],
                'SubTeams' => ['conditions' => ['SubTeams.group_id IN' => clone $groups]],
            ]);

        return $query;
    }

    /**
     * Merge roles by UUID so a role is never returned twice.
     *
     * @param array<\App\Model\Entity\Role> $roles Team roles.
     * @param iterable<array<mixed>|\Cake\Datasource\EntityInterface> $additionalRoles Subteam lead roles.
     * @return array<\App\Model\Entity\Role>
     */
    private function mergeRoles(array $roles, iterable $additionalRoles): array
    {
        $rolesById = [];
        foreach ($roles as $role) {
            $rolesById[$role->id] = $role;
        }
        foreach ($additionalRoles as $role) {
            if (!$role instanceof Role) {
                throw new RuntimeException('Expected a hydrated role entity');
            }
            $rolesById[$role->id] = $role;
        }

        return array_values($rolesById);
    }

    /**
     * Return the concrete teams table used by this endpoint.
     *
     * @return \App\Model\Table\TeamsTable
     */
    private function teamsTable(): TeamsTable
    {
        $table = $this->fetchTable($this->tableAlias);
        if (!$table instanceof TeamsTable) {
            throw new RuntimeException('Expected the Teams table');
        }

        return $table;
    }
}
