<?php

namespace SuperElf\Achievement\Badge;

use SuperElf\Achievement\BadgeCategory;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;

class Calculator
{
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
            $highestPoints = 0;
            foreach( $pool->getUsers() as $poolUser) {
                $currentPoints = $poolUser->getTotalPoints($points, $badgeCategory);
                if( $currentPoints > $highestPoints ) {
                    $bestPoolUsers = [$poolUser];
                    $highestPoints = $currentPoints;
                } else if( $currentPoints === $highestPoints and $highestPoints > 0 ) {
                    $bestPoolUsers[] = $poolUser;
                }
            }
        }
        return $bestPoolUsers;
    }
}
