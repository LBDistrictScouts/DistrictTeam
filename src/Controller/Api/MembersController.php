<?php
declare(strict_types=1);

namespace App\Controller\Api;

class MembersController extends AppController
{
    protected string $tableAlias = 'Members';

    protected array $contain = ['MemberContactMethods'];

    protected array $order = ['Members.last_name' => 'ASC', 'Members.first_name' => 'ASC'];
}
