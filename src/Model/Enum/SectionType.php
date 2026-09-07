<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\Attribute\Label;
use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumLabelTrait;

enum SectionType: string implements EnumLabelInterface
{
    use EnumLabelTrait;

    #[Label('Squirrels')]
    case EarlyYears = 'earlyyears';
    case Beavers = 'beavers';
    case Cubs = 'cubs';
    case Scouts = 'scouts';
    case Explorers = 'explorers';

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
