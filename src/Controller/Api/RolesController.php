<?php
declare(strict_types=1);

namespace App\Controller\Api;

class RolesController extends AppController
{
    protected string $tableAlias = 'Roles';

    protected array $contain = [
        'Teams.Groups',
        'Teams.Sections',
        'CurrentAppointments.Members',
        'CurrentAppointments.MemberContactMethods',
    ];

    protected array $order = ['Teams.tree_left' => 'ASC', 'Roles.name' => 'ASC'];
}
