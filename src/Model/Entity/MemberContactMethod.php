<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\ContactMethodType;
use Cake\ORM\Entity;

/**
 * MemberContactMethod Entity
 *
 * @property string $id
 * @property string $member_id
 * @property string $contact_method
 * @property \App\Model\Enum\ContactMethodType $contact_method_type
 * @property bool $is_non_group_email
 *
 * @property \App\Model\Entity\Member $member
 */
class MemberContactMethod extends Entity
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
        'member_id' => true,
        'contact_method' => true,
        'contact_method_type' => true,
        'is_non_group_email' => false,
        'member' => true,
    ];

    /**
     * Serialize the enum using its human-readable label.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $contactMethodType = $data['contact_method_type'] ?? null;

        if ($contactMethodType instanceof ContactMethodType) {
            $data['contact_method_type'] = $contactMethodType->label();
        }

        return $data;
    }
}
