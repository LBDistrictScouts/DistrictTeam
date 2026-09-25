<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/** A completed CSV import and its summary. */
class ImportFile extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'filename' => true,
        'imported_at' => true,
        'source_record_count' => true,
        'record_count' => true,
        'member_count' => true,
        'contact_count' => true,
        'appointment_count' => true,
        'warning_count' => true,
        'import_records' => true,
    ];
}
