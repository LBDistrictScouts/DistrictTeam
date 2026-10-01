<?php
declare(strict_types=1);

namespace App\Controller\Api;

class EmailGroupsController extends AppController
{
    protected string $tableAlias = 'EmailGroups';

    /**
     * @var array<string>
     */
    protected array $contain = ['Groups', 'Teams', 'Sections'];

    /**
     * @var array<string, string>
     */
    protected array $order = ['EmailGroups.email_group_name' => 'ASC'];
}
