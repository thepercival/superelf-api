<?php
declare(strict_types=1);

namespace SuperElf;

use Sports\Sport;
use Sports\Sport\Config\Service as SportConfigService;
use SuperElf\CompetitionCreator\DefaultCreator;

class CompetitionsCreator
{
    public function __construct() {

    }

    /**
     * @param Pool $pool
     * @param array<int, Sport> $competitionTypes
     */
    public function createCompetitions( Pool $pool, array $competitionTypes ): void {
        $defaultCreator = new DefaultCreator();
        foreach( $competitionTypes as $competitionType => $sport) {
            $defaultCreator->createCompetition( $pool, $competitionType, $sport );
        }
        //$this->createCup( $sport, $pool );
        //$this->createSuperCup( $sport, $pool );
    }

    public function recreateDetails( Pool $pool ): void {

        $defaultCreator = new DefaultCreator();
        $defaultCreator->recreateDetails( $pool );
        //$this->createCup( $sport, $pool );
        //$this->createSuperCup( $sport, $pool );
    }
}