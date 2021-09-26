<?php
declare(strict_types=1);

namespace SuperElf\CompetitionCreator;

use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competition\Sport\Service as CompetitionSportService;
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

    public function __construct() {
       //  $this->sportConfigService = new SportConfigService();
    }

    protected function createCompetitionHelper( Pool $pool, int $competitionType, Sport $sport ): Competition {
        $leagueName = $pool->getCollection()->getLeagueName( $competitionType );
        $season = $pool->getSeason();
        $competition = new Competition( new League( $pool->getCollection()->getAssociation(), $leagueName), $season );
        $competition->setStartDateTime( $season->getStartDateTime() );
        new CompetitionSport(
            $sport,
            $competition,
            $this->convertSportToPersistVariant($sport)
        );
        // (new CompetitionSportService())->addToStructure($newCompetitionSport, $structure);
        return $competition;
    }

    protected function convertSportToPersistVariant(Sport $sport): PersistVariant {
        return new PersistVariant(
            $sport->getDefaultGameMode(),
            $sport->getDefaultNrOfSidePlaces(),
            $sport->getDefaultNrOfSidePlaces(),
            0,
            $sport->getDefaultGameMode() === GameMode::AGAINST ? 3 : 0,
            2
        );
    }

    public abstract function recreateDetails( Pool $pool ): void;
    protected abstract function recreateStructure( Competition $competition, Pool $pool ): Poule;
    /**
     * @param Competition $competition
     * @param Pool $pool
     * @return list<Competitor>
     */
    protected abstract function recreateCompetitors( Competition $competition, Pool $pool ): array;
    /**
     * @param Competition $competition
     * @param Pool $pool
     * @param Poule $poule
     * @param list<Competitor> $competitors
     */
    protected abstract function recreateGames( Competition $competition, Pool $pool, Poule $poule, array $competitors): void;
}