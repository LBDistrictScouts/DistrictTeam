<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;

class TeamsController extends AppController
{
    protected string $tableAlias = 'Teams';

    protected array $contain = ['ParentTeam', 'TeamLead', 'SubTeams.TeamLead'];

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

        $teams = $this->fetchTable($this->tableAlias);
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
     * @param array<\App\Model\Entity\Role> $additionalRoles Subteam lead roles.
     * @return array<\App\Model\Entity\Role>
     */
    private function mergeRoles(array $roles, array $additionalRoles): array
    {
        $rolesById = [];
        foreach (array_merge($roles, $additionalRoles) as $role) {
            $rolesById[$role->id] = $role;
        }

        return array_values($rolesById);
    }
}
