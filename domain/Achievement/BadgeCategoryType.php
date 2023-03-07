<?php


declare(strict_types=1);

namespace SuperElf\Achievement;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use SportsHelpers\EnumDbType;

class BadgeCategoryType extends EnumDbType
{
    public static function getNameHelper(): string
    {
        return 'enum_BadgeCategory';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === BadgeCategory::Result->value) {
            return BadgeCategory::Result;
        }
        if ($value === BadgeCategory::Goal->value) {
            return BadgeCategory::Goal;
        }
        if ($value === BadgeCategory::Assist->value) {
            return BadgeCategory::Assist;
        }
        if ($value === BadgeCategory::Sheet->value) {
            return BadgeCategory::Sheet;
        }
        if ($value === BadgeCategory::Card->value) {
            return BadgeCategory::Card;
        }
        return null;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return 'varchar(10)';
    }
}
