<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Role Entity
 *
 * @property string $id
 * @property string $team_id
 * @property string $group_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $currently_filled
 * @property bool $is_lead
 * @property bool $multi_member_role
 * @property string $staffing_status
 *
 * @property \App\Model\Entity\Team $team
 * @property \App\Model\Entity\Group $group
 * @property array<\App\Model\Entity\Appointment> $appointments
 * @property array<\App\Model\Entity\Appointment> $current_appointments
 */
class Role extends Entity
{
    /**
     * Derived status used to distinguish a filled single-member role from one still recruiting.
     *
     * @var list<string>
     */
    protected array $_virtual = ['staffing_status'];

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
        'team_id' => true,
        'group_id' => false,
        'name' => true,
        'slug' => false,
        'description' => true,
        'currently_filled' => true,
        'is_lead' => true,
        'multi_member_role' => true,
        'team' => true,
    ];

    /**
     * Set the role name and generate its URL-encoded slug.
     *
     * @param string $name Role name.
     * @return string
     */
    protected function _setName(string $name): string
    {
        $this->slug = $name
                |> strtolower(...)
                |> (fn($x) => str_replace(' ', '-', $x))
                |> (fn($x) => str_replace('&', 'and', $x))
                |> urlencode(...)
                |> (fn($x) => str_replace('---', '-', $x));

        return $name;
    }

    /**
     * @return string
     */
    protected function _getStaffingStatus(): string
    {
        if ($this->multi_member_role) {
            return 'recruiting';
        }

        return $this->currently_filled ? 'filled' : 'vacant';
    }
}
