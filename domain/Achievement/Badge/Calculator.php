<?php

namespace SuperElf\Achievement\Badge;

use SuperElf\Achievement\BadgeCategory;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;

class Calculator
{
    public const MIN_NR_OF_POOLUSERS = 5;

    public function __construct()
    {
    }

    /**
     * @param Pool $pool
     * @param BadgeCategory $badgeCategory
     * @return list<PoolUser>
     * @throws \Exception
     */
    public function getBestPoolUsers(Pool $pool, BadgeCategory $badgeCategory): array
    {
        $bestPoolUsers = [];
        {
            $points = $pool->getCompetitionConfig()->getPoints();
            $highestPoints = null;
            foreach( $pool->getUsers() as $poolUser) {
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
