<?php

namespace SuperElf\Achievement\Trophy;

use Sports\Competition;
use Sports\Competitor\StartLocation;
use Sports\Competitor\StartLocationMap;
use Sports\Structure;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Competitor as S11Competitor;
use Sports\Ranking\Calculator\End as EndRankingCalculator;

class Calculator
{
    public function __construct()
    {
    }

    /**
     * @param Pool $pool
     * @param Competition $poolCompetition
     * @param int $rank
     * @param Structure $structure
     * @return list<PoolUser>
     * @throws \Exception
     */
    public function getPoolUsersByRank(
        Pool $pool,
        Competition $poolCompetition,
        int $rank,
        Structure $structure): array
    {
        $firstCategory = $structure->getSingleCategory();
        $calculator = new EndRankingCalculator($firstCategory);

        $poolUsers = [];
        {
            $startLocationMap = new StartLocationMap($pool->getCompetitors($poolCompetition));
            $items = $calculator->getItems();
            $endRankingItem = array_shift($items);
            while ($endRankingItem !== null ) {
                if( $endRankingItem->getRank() === $rank ) {
                    $startLocation = $endRankingItem->getStartLocation();
                    if( $startLocation !== null ) {
                        $poolCompetitor = $startLocationMap->getCompetitor($startLocation);
                        if( $poolCompetitor !== null and $poolCompetitor instanceof S11Competitor) {
                            $poolUsers[] = $poolCompetitor->getPoolUser();
                        }
                    }
                }
                $endRankingItem = array_shift($items);
            }
        }
        return $poolUsers;
    }
}
