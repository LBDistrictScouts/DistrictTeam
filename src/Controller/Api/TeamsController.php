<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
use App\Model\Table\TeamsTable;
use RuntimeException;

class TeamsController extends AppController
{
    protected string $tableAlias = 'Teams';

    protected array $contain = ['ParentTeam', 'TeamLead', 'SubTeams.TeamLead'];

    /**
     * Associations included in individual team responses.
     *
     * @var array<int|string, mixed>
     */
    protected array $viewContain = [
        'ParentTeam',
        'TeamLead',
        'SubTeams.TeamLead',
        'Roles' => [
            'fields' => [
                'Roles.id',
                'Roles.team_id',
                'Roles.name',
                'Roles.slug',
                'Roles.currently_filled',
                'Roles.is_lead',
            ],
        ],
        'Roles.CurrentAppointment' => [
            'fields' => [
                'CurrentAppointment.id',
                'CurrentAppointment.role_id',
                'CurrentAppointment.member_id',
            ],
        ],
        'Roles.CurrentAppointment.Members' => [
            'fields' => [
                'Members.id',
                'Members.first_name',
                'Members.last_name',
            ],
        ],
    ];

    protected array $order = ['Teams.tree_left' => 'ASC'];

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
        $team = $teams->get($id, contain: $this->viewContain);
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
                ])
                ->where([
                    'Roles.team_id IN' => $subTeamIds,
                    'Roles.is_lead' => true,
                ])
                ->contain([
                    'CurrentAppointment' => [
                        'fields' => [
                            'CurrentAppointment.id',
                            'CurrentAppointment.role_id',
                            'CurrentAppointment.member_id',
                        ],
                    ],
                    'CurrentAppointment.Members' => [
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
