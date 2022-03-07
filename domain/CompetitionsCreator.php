<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Sport;
use SuperElf\Competitions\BaseCreator;
use SuperElf\Competitions\CompetitionCreator;
use SuperElf\Competitions\CupCreator;
use SuperElf\Competitions\SuperCupCreator;
use SuperElf\League as S11League;

class CompetitionsCreator
{
    public function __construct()
    {
    }

    /**
     * @param Pool $pool
     * @param Sport $sport
     * @return list<Competition>
     */
    public function createCompetitions(Pool $pool, Sport $sport): array
    {
        $competitions = [];
        $competitionCreator = $this->getCreator(S11League::Competition);
        $competitions[] = $competitionCreator->createCompetition($pool, $sport);

        // @TODO DEPRECATED CDK
        if ($pool->getSeason()->getStartDateTime() > (new \DateTimeImmutable('2022-01-01'))
            ||
            ($pool->getSeason()->getStartDateTime() > (new \DateTimeImmutable('2020-01-01'))
                && $pool->getCollection()->getName() === 'kamp duim')
        ) {
            $cupCreator = $this->getCreator(S11League::Cup);
            $competitions[] = $cupCreator->createCompetition($pool, $sport);
        }

        $previous = $pool->getUnhaltedPrevious();
        if ($previous !== null && $previous->getCompetition(S11League::Cup) !== null) {
            $superCupCreator = $this->getCreator(S11League::SuperCup);
            $competitions[] = $superCupCreator->createCompetition($pool, $sport);
        }

        return $competitions;
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
