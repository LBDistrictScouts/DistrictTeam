<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Database\Type\EnumLabelTrait;

enum GroupType: string implements EnumLabelInterface
{
    use EnumLabelTrait;

    case Group = 'group';

    case District = 'district';
}
