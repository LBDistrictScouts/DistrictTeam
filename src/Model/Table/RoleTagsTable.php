<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class RoleTagsTable extends Table
{
    /** @param array<string, mixed> $config */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('role_tags');
        $this->setPrimaryKey(['role_id', 'tag_id']);
        $this->belongsTo('Roles', ['foreignKey' => 'role_id', 'joinType' => 'INNER']);
        $this->belongsTo('Tags', ['foreignKey' => 'tag_id', 'joinType' => 'INNER']);
    }
}
