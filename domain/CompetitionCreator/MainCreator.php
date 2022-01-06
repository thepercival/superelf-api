<?php

declare(strict_types=1);

namespace SuperElf\CompetitionCreator;

use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\League;
use Sports\Poule;
use Sports\Sport;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\Competitor;
use SuperElf\Pool;

abstract class MainCreator
{
    // protected SportConfigService $sportConfigService;

    public function __construct()
    {
        //  $this->sportConfigService = new SportConfigService();
    }

    protected function createCompetitionHelper(
        Pool $pool,
        Sport $sport,
        int $competitionType
    ): Competition {
        $leagueName = $pool->getCollection()->getLeagueName($competitionType);
        $season = $pool->getSeason();
        $league = new League($pool->getCollection()->getAssociation(), $leagueName);
        $competition = new Competition($league, $season);
        $competition->setStartDateTime($season->getStartDateTime());
        new CompetitionSport(
            $sport,
            $competition,
            $this->convertSportToPersistVariant($sport)
        );
        // (new CompetitionSportService())->addToStructure($newCompetitionSport, $structure);
        return $competition;
    }

    protected function convertSportToPersistVariant(Sport $sport): PersistVariant
    {
        return $sport->createTogetherPersistVariant(
            GameMode::AllInOneGame,
            0,
            1
        );
    }

    abstract public function recreateDetails(Pool $pool): void;
    abstract protected function recreateStructure(Competition $competition, Pool $pool): Poule;
    /**
     * @param Competition $competition
     * @param Pool $pool
     * @return list<Competitor>
     */
    abstract protected function recreateCompetitors(Competition $competition, Pool $pool): array;
    /**
     * @param Competition $competition
     * @param Pool $pool
     * @param Poule $poule
     * @param list<Competitor> $competitors
     */
    abstract protected function recreateGames(Competition $competition, Pool $pool, Poule $poule, array $competitors): void;
}
