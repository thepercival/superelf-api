<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Sport;
use SuperElf\Competitions\BaseCreator;
use SuperElf\Competitions\CompetitionCreator;
use SuperElf\Competitions\CupCreator;
use SuperElf\Competitions\SuperCupCreator;

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
        $competitionCreator = $this->getCreator(CompetitionType::COMPETITION);
        $competitions[] = $competitionCreator->createCompetition($pool, $sport);

        // @TODO DEPRECATED CDK
        if ($pool->getSeason()->getStartDateTime() > (new \DateTimeImmutable('2022-01-01'))
            ||
            ($pool->getSeason()->getStartDateTime() > (new \DateTimeImmutable('2020-01-01'))
                && $pool->getCollection()->getName() === 'kamp duim')
        ) {
            $cupCreator = $this->getCreator(CompetitionType::CUP);
            $competitions[] = $cupCreator->createCompetition($pool, $sport);
        }

        $previous = $pool->getUnhaltedPrevious();
        if ($previous !== null && $previous->getCompetition(CompetitionType::CUP) !== null) {
            $superCupCreator = $this->getCreator(CompetitionType::SUPERCUP);
            $competitions[] = $superCupCreator->createCompetition($pool, $sport);
        }

        return $competitions;
    }

    public function createCompetitionDetails(Pool $pool): void
    {
        $competitionTypes = [
            CompetitionType::COMPETITION,
            CompetitionType::CUP,
            CompetitionType::SUPERCUP
        ];
        foreach ($competitionTypes as $competitionType) {
            $competition = $pool->getCompetition($competitionType);
            if ($competition === null) {
                continue;
            }
            $this->getCreator($competitionType)->createCompetitionDetails($pool);
        }
    }

    protected function getCreator(int $competitionType): BaseCreator
    {
        if ($competitionType === CompetitionType::COMPETITION) {
            return new CompetitionCreator();
        } elseif ($competitionType === CompetitionType::CUP) {
            return new CupCreator();
        } elseif ($competitionType === CompetitionType::SUPERCUP) {
            return new SuperCupCreator();
        }
        throw new \Exception('unknown competitiontype', E_ERROR);
    }
}
