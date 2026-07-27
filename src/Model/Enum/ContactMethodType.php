<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumLabelTrait;

/**
 * ContactMethodType Enum
 */
enum ContactMethodType: int implements EnumLabelInterface
{
    use EnumLabelTrait;

    case Email = 1;

    case EmailAlias = 2;

    case EmailGroup = 3;

    case PhoneNumber = 10;
}
