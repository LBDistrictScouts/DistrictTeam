<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/** A remembered Group and Section destination for a CSV unit and parent unit. */
class CsvUnitMapping extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'source_key' => true,
        'source_unit' => true,
        'source_parent_unit' => true,
        'group_id' => true,
        'section_id' => true,
    ];
}
