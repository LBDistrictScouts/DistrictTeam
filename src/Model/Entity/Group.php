<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Group Entity
 *
 * @property string $id
 * @property \App\Model\Enum\GroupType|null $type
 * @property list<string>|null $domains
 * @property string $group_name
 * @property int|null $group_osm_id
 * @property int|null $sort_order
 * @property int $sections_count
 * @property int $teams_count
 * @property int $roles_count
 *
 * @property \App\Model\Entity\Section[] $sections
 * @property \App\Model\Entity\Team[] $teams
 */
class Group extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'group_name' => true,
        'type' => true,
        'domains' => true,
        'group_osm_id' => true,
        'sort_order' => true,
        'sections' => false,
    ];
}
