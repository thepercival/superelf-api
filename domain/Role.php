<?php

declare(strict_types=1);

namespace SuperElf;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Role
{
    public const int COMPETITOR = 1;
    public const int ADMIN = 2;
    public const int SYSADMIN = 4;

    public static function getName(int $role): string
    {
        if ($role === self::COMPETITOR) {
            return 'pool-deelnemer';
        } elseif ($role === self::ADMIN) {
            return 'pool-beheerder';
        } else {
            if ($role === self::SYSADMIN) {
                return 'systeem-beheerder';
            }
        }
        return 'onbekend';
    }
}
