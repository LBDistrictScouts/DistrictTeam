<?php
declare(strict_types=1);

namespace App\Controller\Api;

class TeamsController extends AppController
{
    protected string $tableAlias = 'Teams';

    protected array $contain = ['ParentTeam', 'SubTeams'];

    protected array $order = ['Teams.tree_left' => 'ASC'];
}
