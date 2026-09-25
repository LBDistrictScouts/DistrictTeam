<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Email Group Entity
 *
 * @property string $id
 * @property string $group_id
 * @property string|null $team_id
 * @property string|null $section_id
 * @property string $email_group_name
 * @property string|null $email_address
 *
 * @property \App\Model\Entity\Group $group
 * @property \App\Model\Entity\Team|null $team
 * @property \App\Model\Entity\Section|null $section
 * @property array<\App\Model\Entity\MemberContactMethod> $member_contact_methods
 * @property array<\App\Model\Entity\Member> $members
 */
class EmailGroup extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'group_id' => true,
        'team_id' => true,
        'section_id' => true,
        'email_group_name' => true,
        'email_address' => true,
        'group' => true,
        'team' => true,
        'section' => true,
        'member_contact_methods' => true,
        'members' => true,
    ];

    /**
     * Store email addresses in a consistent form.
     *
     * @param string $emailAddress Email address.
     * @return string
     */
    protected function _setEmailAddress(string $emailAddress): string
    {
        return strtolower($emailAddress);
    }
}
