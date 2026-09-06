<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Table\RolesTable;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validation;
use RuntimeException;

class RolesController extends AppController
{
    protected string $tableAlias = 'Roles';

    private ?string $groupId = null;

    protected array $contain = [
        'Teams.Groups',
        'Teams.Sections',
        'CurrentAppointments.Members',
        'CurrentAppointments.MemberContactMethods',
    ];

    protected array $order = ['Teams.tree_left' => 'ASC', 'Roles.name' => 'ASC'];

    /**
     * Return a group's roles using the standard role collection response.
     *
     * @param string $groupUUID Shared core-data group UUID.
     * @return void
     */
    public function groupRoles(string $groupUUID): void
    {
        $this->request->allowMethod(['get']);
        if (!Validation::uuid($groupUUID)) {
            throw new NotFoundException('Group not found.');
        }
        $group = $this->rolesTable()->Groups->get($groupUUID);
        $this->groupId = $group->id;
        parent::index();
    }

    /**
     * Build the role collection query, optionally limited to one group.
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function collectionQuery(): SelectQuery
    {
        $query = parent::collectionQuery();
        if ($this->groupId !== null) {
            $query->where(['Roles.group_id' => $this->groupId]);
        }

        return $query;
    }

    /**
     * Return the concrete roles table used by this endpoint.
     *
     * @return \App\Model\Table\RolesTable
     */
    private function rolesTable(): RolesTable
    {
        $table = $this->fetchTable($this->tableAlias);
        if (!$table instanceof RolesTable) {
            throw new RuntimeException('Expected the Roles table');
        }

        return $table;
    }
}
