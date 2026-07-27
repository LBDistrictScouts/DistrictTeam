<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Appointment Entity
 *
 * @property string $id
 * @property string $role_id
 * @property string $member_id
 * @property string $member_contact_method_id
 * @property \Cake\I18n\Date $effective_start_date
 * @property \Cake\I18n\Date|null $effective_end_date
 * @property bool $active
 *
 * @property \App\Model\Entity\Role $role
 * @property \App\Model\Entity\Member $member
 * @property \App\Model\Entity\MemberContactMethod $member_contact_method
 */
class Appointment extends Entity
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
        'role_id' => true,
        'member_id' => true,
        'member_contact_method_id' => true,
        'effective_start_date' => true,
        'effective_end_date' => true,
        'active' => true,
        'role' => true,
        'member' => true,
        'member_contact_method' => true,
    ];
}
