<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumLabelTrait;

enum RoleTemplate: string implements EnumLabelInterface
{
    use EnumLabelTrait;

    case GroupLeadVolunteer = 'group-lead-volunteer';
    case GroupLeadershipTeamMember = 'group-leadership-team-member';
    case SquirrelSectionTeamLeader = 'squirrel-section-team-leader';
    case SquirrelSectionTeamMember = 'squirrel-section-team-member';
    case BeaverSectionTeamLeader = 'beaver-section-team-leader';
    case BeaverSectionTeamMember = 'beaver-section-team-member';
    case CubSectionTeamLeader = 'cub-section-team-leader';
    case CubSectionTeamMember = 'cub-section-team-member';
    case ScoutSectionTeamLeader = 'scout-section-team-leader';
    case ScoutSectionTeamMember = 'scout-section-team-member';
    case TrusteeBoardChair = 'trustee-board-chair';
    case GroupTreasurer = 'group-treasurer';
    case TrusteeBoardMember = 'trustee-board-member';

    /**
     * Return form-ready template values and labels.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $template) {
            $options[$template->value] = $template->label();
        }

        return $options;
    }
}
