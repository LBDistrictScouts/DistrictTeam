<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Tag Entity
 *
 * @property string $id
 * @property string $tag_label
 * @property string $tag_code
 * @property bool $is_cognito_permissions_group
 * @property string|null $email_group_id
 * @property \App\Model\Entity\EmailGroup|null $email_group
 * @property array<\App\Model\Entity\Team> $teams
 * @property array<\App\Model\Entity\Role> $roles
 * @property array<\App\Model\Entity\Appointment> $appointments
 * @property list<array{id: string, member_id: string, member_contact_method_id: string, member_first_name: string, member_last_name: string, role_name: string, contact_method: string}> $current_appointments
 */
class Tag extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'tag_label' => true,
        'tag_code' => true,
        'is_cognito_permissions_group' => true,
        'email_group_id' => true,
        'email_group' => true,
        'teams' => true,
        'roles' => true,
        'appointments' => true,
    ];
}
