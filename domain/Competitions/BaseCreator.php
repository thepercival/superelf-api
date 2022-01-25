<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\League;
use Sports\Poule;
use Sports\Sport;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\Competitor;
use SuperElf\Pool;

abstract class BaseCreator
{
    public function __construct(protected int $competitionType)
    {
    }

    public function createCompetition(Pool $pool, Sport $sport): Competition
    {
        $leagueName = $pool->getCollection()->getLeagueName($this->competitionType);
        $season = $pool->getSeason();
        $league = new League($pool->getCollection()->getAssociation(), $leagueName);
        $competition = new Competition($league, $season);
        $competition->setStartDateTime($season->getStartDateTime());
        new CompetitionSport(
            $sport,
            $competition,
            $this->convertSportToPersistVariant($sport)
        );
        return $competition;
    }

    abstract protected function convertSportToPersistVariant(Sport $sport): PersistVariant;

    public function recreateCompetitionDetails(Pool $pool): void
    {
        $competition = $pool->getCompetition($this->competitionType);
        if ($competition === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        $poule = $this->createStructure($competition, $pool);
        $competitors = $this->recreateCompetitors($competition, $pool);
        $this->recreateGames($competition, $pool, $poule, $competitors);
    }

    abstract protected function createStructure(Competition $competition, Pool $pool): Poule;
    /**
     * @param Competition $competition
     * @param Pool $pool
     * @return list<Competitor>
     */
    abstract protected function createCompetitors(Competition $competition, Pool $pool): array;
    /**
     * @param Competition $competition
     * @param Pool $pool
     * @param Poule $poule
     * @param list<Competitor> $competitors
     */
    abstract protected function createGames(
        Competition $competition,
        Pool $pool,
        Poule $poule,
        array $competitors
    ): void;
}
