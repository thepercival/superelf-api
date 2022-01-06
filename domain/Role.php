<?php

declare(strict_types=1);

namespace SuperElf;

class Role
{
    public const COMPETITOR = 1;
    public const ADMIN = 2;
    public const SYSADMIN = 4;

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
