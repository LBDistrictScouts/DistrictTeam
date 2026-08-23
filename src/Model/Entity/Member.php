<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Member Entity
 *
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property int $membership_number
 * @property \Cake\I18n\Date $join_date
 * @property \Cake\I18n\Date|null $leave_date
 * @property bool $active
 * @property-read string $full_name
 * @property array<\App\Model\Entity\MemberContactMethod> $member_contact_methods
 */
class Member extends Entity
{
    /**
     * Fields excluded from serialized representations.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'membership_number',
    ];

    /**
     * Virtual fields exposed by the entity.
     *
     * @var array<string>
     */
    protected array $_virtual = [
        'full_name',
    ];

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
        'first_name' => true,
        'last_name' => true,
        'membership_number' => true,
        'join_date' => true,
        'leave_date' => true,
        'active' => true,
        'member_contact_methods' => true,
    ];

    /**
     * Get the member's full name.
     *
     * @return string
     */
    protected function _getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
