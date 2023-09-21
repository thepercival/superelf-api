<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Game\State;
use Sports\Ranking\Calculator\End as EndRankingCalculator;
use Sports\Ranking\PointsCalculation;
use Sports\Sport;
use Sports\Structure\Repository as StructureRepository;
use SuperElf\Competitions\BaseCreator;
use SuperElf\Competitions\CompetitionCreator;
use SuperElf\Competitions\CupCreator;
use SuperElf\Competitions\SuperCupCreator;
use SuperElf\Competitions\WorldCupCreator;
use SuperElf\League as S11League;
use SuperElf\Pool\User as PoolUser;

class CompetitionsCreator
{
    public function __construct(
        protected StructureRepository $structureRepos
    ) {
    }

    public function createCompetition(
        Pool $pool,
        Sport $sport,
        S11League $league
    ): Competition|null {
        $validPoolUsers = $this->getValidPoolUsers($pool, $league);
        if (count($validPoolUsers) < 2) {
            return null;
        }

        if ($league === S11League::Competition) {
            $competitionCreator = $this->getCreator(S11League::Competition);
            return $competitionCreator->createCompetition($pool, $sport, PointsCalculation::Scores);
        }


        if ($league === S11League::Cup) {
            if ($pool->getSeason()->getStartDateTime() > (new \DateTimeImmutable('2022-01-01'))
                ||
                ($pool->getSeason()->getStartDateTime() > (new \DateTimeImmutable('2020-01-01'))
                    && $pool->getCollection()->getName() === 'kamp duim')
            ) {
                $cupCreator = $this->getCreator(S11League::Cup);
                return $cupCreator->createCompetition($pool, $sport, PointsCalculation::AgainstGamePoints);
            }
            return null;
        }

        $superCupCreator = $this->getCreator(S11League::SuperCup);
        return $superCupCreator->createCompetition($pool, $sport, PointsCalculation::AgainstGamePoints);
    }

    /**
     * @param Pool $pool
     * @param S11League $league
     * @return list<PoolUser>
     */
    public function getInvalidPoolUsers(Pool $pool, S11League $league): array
    {
        return array_values(
            $pool->getUsers()->filter(function (PoolUser $poolUser): bool {
                return !$poolUser->canCompete();
            })->toArray()
        );
    }

    /**
     * @param Pool $pool
     * @param S11League $league
     * @return list<PoolUser>
     */
    public function getValidPoolUsers(Pool $pool, S11League $league): array
    {
        if ($league === S11League::SuperCup) {
            return $this->getValidSuperCupPoolUsers($pool);
        } else if ($league === S11League::WorldCup) {
            return $this->getValidWorldCupPoolUsers($pool);
        }
        return array_values($pool->getUsers()->toArray());
    }

    /**
     * @param Pool $pool
     * @param S11League $league
     * @return list<PoolUser>
     */
    public function getValidSuperCupPoolUsers(Pool $pool): array
    {
       $validPoolUsers = array_values($pool->getUsers()->toArray());


        $validPoolUsersSuperCup = [];
        $previous = $pool->getUnhaltedPrevious();
        /*if ($pool->getName() === 'kamp duim' and $pool->getSeason()->getName() === '2022/2023') {
            $poolUsersCoen = array_filter($validPoolUsers, function (PoolUser $poolUser): bool {
                return $poolUser->getUser()->getName() === 'coen';
            });
            $poolUserCoen = array_pop($poolUsersCoen);
            if ($poolUserCoen !== null) {
                $validPoolUsersSuperCup[] = $poolUserCoen;
            }
            $poolUsersBets = array_filter($validPoolUsers, function (PoolUser $poolUser): bool {
                return $poolUser->getUser()->getName() === 'bets';
            });
            $poolUserBets = array_pop($poolUsersBets);
            if ($poolUserBets !== null) {
                $validPoolUsersSuperCup[] = $poolUserBets;
            }
            return $validPoolUsersSuperCup;
        } else*/

        if ($previous === null) {
            return [];
        }

        $bestPoolUsersCup = $this->getBestValidPoolUsers($previous, S11League::Cup, $validPoolUsers, 1);
        $bestPoolUserCup = reset($bestPoolUsersCup);
        if ($bestPoolUserCup !== false) {
            $validPoolUsersSuperCup[] = $bestPoolUserCup;
            $validPoolUsers = array_values(
                array_filter($validPoolUsers, function( PoolUser $validPoolUser ) use ($bestPoolUserCup): bool {
                return $validPoolUser !== $bestPoolUserCup;
            } ) );
        }


        $bestPoolUsersCompetition = $this->getBestValidPoolUsers($previous, S11League::Competition, $validPoolUsers, 1);
        $bestPoolUserCompetition = reset($bestPoolUsersCompetition);
        if ($bestPoolUserCompetition !== false) {
            $validPoolUsersSuperCup[] = $bestPoolUserCompetition;
        }

        return $validPoolUsersSuperCup;
    }

    /**
     * @param Pool $pool
     * @param S11League $league
     * @return list<PoolUser>
     */
    public function getValidWorldCupPoolUsers(Pool $pool): array
    {
        $validPoolUsers = array_values($pool->getUsers()->toArray());

        $previous = $pool->getUnhaltedPrevious();
        /*if ($pool->getName() === 'kamp duim' and $pool->getSeason()->getName() === '2022/2023') {
            $poolUsersCoen = array_filter($validPoolUsers, function (PoolUser $poolUser): bool {
                return $poolUser->getUser()->getName() === 'coen';
            });
            $poolUserCoen = array_pop($poolUsersCoen);
            if ($poolUserCoen !== null) {
                $validPoolUsersSuperCup[] = $poolUserCoen;
            }
            $poolUsersBets = array_filter($validPoolUsers, function (PoolUser $poolUser): bool {
                return $poolUser->getUser()->getName() === 'bets';
            });
            $poolUserBets = array_pop($poolUsersBets);
            if ($poolUserBets !== null) {
                $validPoolUsersSuperCup[] = $poolUserBets;
            }
            return $validPoolUsersSuperCup;
        } else*/
        if ($previous === null) {
            return [];
        }
        return  $this->getBestValidPoolUsers($previous, S11League::Competition, $validPoolUsers, 2);
    }


    /**
     * @param Pool $previousPool
     * @param League $league
     * @param list<PoolUser> $validPoolUsers
     * @return list<PoolUser>
     * @throws \Sports\Exceptions\StructureNotFoundException
     */
    protected function getBestValidPoolUsers(Pool $previousPool, S11League $league, array $validPoolUsers, int $max): array
    {
        $previousCompetition = $previousPool->getCompetition($league);
        if ($previousCompetition === null) {
            return [];
        }
        $previousCategory = $this->structureRepos->getStructure($previousCompetition)->getSingleCategory();
        if ($previousCategory->getGamesState() !== State::Finished) {
            return [];
        }
        $endRankingCalculator = new EndRankingCalculator($previousCategory);
        $rankingItems = $endRankingCalculator->getItems();
        $bestValidPoolUsers = [];
        foreach ($rankingItems as $rankingItem) {
            $rankingStartLocation = $rankingItem->getStartLocation();
            if ($rankingStartLocation === null) {
                continue;
            }
            foreach( $previousPool->getUsers() as $previousPoolUser) {
                $previousCompetitor = $previousPoolUser->getCompetitor($previousCompetition);
                if ($previousCompetitor !== null && $previousCompetitor->equals($rankingStartLocation)) {
                    $user = $previousCompetitor->getPoolUser()->getUser();
                    foreach ($validPoolUsers as $validPoolUser) {
                        if ( $validPoolUser->getUser() === $user ) {
                            $bestValidPoolUsers[] = $validPoolUser;
                            break;
                        }
                    }
                }
            }
            if( count($bestValidPoolUsers) === $max ) {
                break;
            }
        }
        return $bestValidPoolUsers;
    }



//    public function createCompetitionDetails(Pool $pool): void
//    {
//        $competitionTypes = [
//            CompetitionType::COMPETITION,
//            CompetitionType::CUP,
//            CompetitionType::SUPERCUP
//        ];
//        foreach ($competitionTypes as $competitionType) {
//            $competition = $pool->getCompetition($competitionType);
//            if ($competition === null) {
//                continue;
//            }
//            $this->getCreator($competitionType)->createCompetitionDetails($pool);
//        }
//    }

    public function getCreator(S11League $s11League): BaseCreator
    {
        if ($s11League === S11League::Competition) {
            return new CompetitionCreator();
        } elseif ($s11League === S11League::Cup) {
            return new CupCreator();
        } elseif ($s11League === S11League::SuperCup) {
            return new SuperCupCreator();
        } elseif ($s11League === S11League::WorldCup) {
            return new WorldCupCreator();
        }
        throw new \Exception('unknown competitiontype', E_ERROR);
    }
}
