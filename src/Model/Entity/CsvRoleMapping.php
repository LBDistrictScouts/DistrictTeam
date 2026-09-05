<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/** A remembered CSV source and its destination; null role_id means skip. */
class CsvRoleMapping extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'source_key' => true,
        'source_unit' => true,
        'source_parent' => true,
        'source_team' => true,
        'source_role' => true,
        'source_type' => true,
        'role_id' => true,
    ];
}
