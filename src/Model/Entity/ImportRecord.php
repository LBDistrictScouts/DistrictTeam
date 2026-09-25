<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * An entity snapshot produced from one source row in an import file.
 *
 * @property \App\Model\Entity\Member|null $member
 * @property \App\Model\Entity\Appointment|null $appointment
 */
class ImportRecord extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'import_file_id' => true,
        'source_line' => true,
        'entity_type' => true,
        'action' => true,
        'reason' => true,
        'member_id' => true,
        'appointment_id' => true,
        'source_data' => true,
        'entity_data' => true,
    ];
}
