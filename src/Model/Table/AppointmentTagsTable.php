<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class AppointmentTagsTable extends Table
{
    /** @param array<string, mixed> $config */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('appointment_tags');
        $this->setPrimaryKey(['appointment_id', 'tag_id']);
        $this->belongsTo('Appointments', ['foreignKey' => 'appointment_id', 'joinType' => 'INNER']);
        $this->belongsTo('Tags', ['foreignKey' => 'tag_id', 'joinType' => 'INNER']);
    }
}
