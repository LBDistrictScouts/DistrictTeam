<?php
declare(strict_types=1);

namespace App\Controller\Api;

class MemberContactMethodsController extends AppController
{
    protected string $tableAlias = 'MemberContactMethods';

    protected array $contain = ['Members'];

    protected array $order = ['MemberContactMethods.contact_method' => 'ASC'];
}
