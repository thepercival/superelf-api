<?php

namespace SuperElf\Achievement\Badge;

use SuperElf\Achievement\BadgeCategory;
use SuperElf\Points;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;

final class Calculator
{
    public const MIN_NR_OF_POOLUSERS = 5;

    public function __construct()
    {
    }

    /**
     * @param list<PoolUser> $poolUsers
     * @param Points $points
     * @param BadgeCategory $badgeCategory
     * @return list<PoolUser>
     * @throws \Exception
     */
    public function getBestPoolUsers(array $poolUsers, Points $points, BadgeCategory $badgeCategory): array
    {
        $bestPoolUsers = [];
        {
            $highestPoints = null;
            foreach( $poolUsers as $poolUser) {
                $currentPoints = $poolUser->getTotalPoints($points, $badgeCategory);
                if( $highestPoints === null || $currentPoints > $highestPoints ) {
                    $bestPoolUsers = [$poolUser];
                    $highestPoints = $currentPoints;
                } else if( $currentPoints === $highestPoints ) {
                    $bestPoolUsers[] = $poolUser;
                }
            }
        }
        return $bestPoolUsers;
    }
}
