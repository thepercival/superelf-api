<?php

declare(strict_types=1);

namespace SuperElf;

class Role
{
    const COMPETITOR = 1;
    const ADMIN = 2;
    const SYSADMIN = 4;

    public static function getName(int $role): string
    {
        if ($role === self::COMPETITOR) {
            return 'pool-deelnemer';
        } else if ($role === self::ADMIN) {
            return 'pool-beheerder';
        } else {
            if ($role === self::SYSADMIN) {
                return 'systeem-beheerder';
            }
        }
        return 'onbekend';
    }
}