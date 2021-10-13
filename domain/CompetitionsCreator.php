<?php
declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Sport;
use Sports\Sport\Config\Service as SportConfigService;
use SuperElf\CompetitionCreator\DefaultCreator;
use SportsHelpers\Sport\PersistVariant as SportPersistVariant;

class CompetitionsCreator
{
    public function __construct() {

    }

    /**
     * @param Pool $pool
     * @param SportPersistVariant $sportPersistVariant
     * @param list<int> $competitionTypes
     * @return list<Competition>
     */
    public function createCompetitions(
        Pool $pool,
        Sport $sport,
        array $competitionTypes): array {
        $defaultCreator = new DefaultCreator();
        $competitions = [];
        foreach( $competitionTypes as $competitionType) {
            $competitions[] = $defaultCreator->createCompetition( $pool, $sport, $competitionType );
        }
        return $competitions;
    }

    public function recreateDetails( Pool $pool ): void {

        $defaultCreator = new DefaultCreator();
        $defaultCreator->recreateDetails( $pool );
        //$this->createCup( $sport, $pool );
        //$this->createSuperCup( $sport, $pool );
    }
}