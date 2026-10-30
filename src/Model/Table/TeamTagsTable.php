<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class TeamTagsTable extends Table
{
    /** @param array<string, mixed> $config */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('team_tags');
        $this->setPrimaryKey(['team_id', 'tag_id']);
        $this->belongsTo('Teams', ['foreignKey' => 'team_id', 'joinType' => 'INNER']);
        $this->belongsTo('Tags', ['foreignKey' => 'tag_id', 'joinType' => 'INNER']);
    }
}
