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
        $validPoolUsers = array_values(
            $pool->getUsers()->filter(function (PoolUser $poolUser) use($pool): bool {
                if( $pool->getSeason()->getStartDateTime()->getTimestamp() < (new \DateTimeImmutable('2015-01-01'))->getTimestamp() ) {
                    return true;
                }
                return $poolUser->canCompete();
            })->toArray()
        );

        if ($league !== S11League::SuperCup) {
            return $validPoolUsers;
        }
        $validPoolUsersSuperCup = [];
        $previous = $pool->getUnhaltedPrevious();
        if ($pool->getName() === 'kamp duim' and $pool->getSeason()->getName() === '2022/2023') {
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
        } elseif ($previous === null) {
            return [];
        }

        $bestPoolUserCompetition = $this->getBestValidPoolUser($previous, S11League::Competition, $validPoolUsers);
        if ($bestPoolUserCompetition !== null) {
            $validPoolUsersSuperCup[] = $bestPoolUserCompetition;
        }
        $bestPoolUserCup = $this->getBestValidPoolUser($previous, S11League::Cup, $validPoolUsers);
        if ($bestPoolUserCup !== null) {
            $validPoolUsersSuperCup[] = $bestPoolUserCup;
        }
        return $validPoolUsersSuperCup;
    }

    /**
     * @param Pool $pool
     * @param League $league
     * @param list<PoolUser> $validPoolUsers
     * @return PoolUser|null
     * @throws \Sports\Exceptions\StructureNotFoundException
     */
    protected function getBestValidPoolUser(Pool $pool, S11League $league, array $validPoolUsers): PoolUser|null
    {
        $competition = $pool->getCompetition($league);
        if ($competition === null) {
            return null;
        }
        $category = $this->structureRepos->getStructure($competition)->getSingleCategory();
        if ($category->getGamesState() !== State::Finished) {
            return null;
        }
        $endRankingCalculator = new EndRankingCalculator($category);
        $rankingItems = $endRankingCalculator->getItems();
        foreach ($rankingItems as $rankingItem) {
            $rankingStartLocation = $rankingItem->getStartLocation();
            if ($rankingStartLocation === null) {
                continue;
            }
            foreach ($validPoolUsers as $validPoolUser) {
                $competitor = $validPoolUser->getCompetitor($competition);
                if ($competitor !== null && $competitor->equals($rankingStartLocation)) {
                    return $validPoolUser;
                }
            }
        }
        return null;
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
        }
        throw new \Exception('unknown competitiontype', E_ERROR);
    }
}
