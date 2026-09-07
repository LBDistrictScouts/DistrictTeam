<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumLabelTrait;

enum TeamTemplate: string implements EnumLabelInterface
{
    use EnumLabelTrait;

    case LeadershipTeam = 'leadership-team';
    case SquirrelSection = 'squirrel-section';
    case BeaverSection = 'beaver-section';
    case CubSection = 'cub-section';
    case ScoutSection = 'scout-section';
    case TrusteeBoard = 'trustee-board';

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
