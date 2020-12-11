<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Sport;
use Sports\Sport\Config\Service as SportConfigService;
use Sports\Competition;
use Sports\League;
use SuperElf\Pool\User as PoolUser;

class CompetitionsCreator
{
    protected SportConfigService $sportConfigService;

    public function __construct() {
        $this->sportConfigService = new SportConfigService();
    }

    public function create( Sport $sport, Pool $pool ) {
        $this->createDefault( $sport, $pool );
        $this->createCup( $sport, $pool );
        $this->createSuperCup( $sport, $pool );
    }

    protected function createDefault( Sport $sport, Pool $pool ) {
        $default = $pool->getCompetition( PoolCollection::LEAGUE_DEFAULT );
        if( $default === null ) {
            $defaultLeagueName = $pool->getCollection()->getLeagueName( PoolCollection::LEAGUE_DEFAULT );
            $season = $pool->getCompetition()->getSeason();
            $default = new Competition( new League( $pool->getCollection()->getAssociation(), $defaultLeagueName), $season );
            $default->setStartDateTime( $season->getStartDateTime() );
            $this->sportConfigService->createDefault( $sport, $default );
        }
        $this->recreateCompetitors( $default, $pool );
    }

    protected function recreateCompetitors( Competition $competition, Pool $pool ) {
        // remove competitors and than create them
    }

    protected function addCompetitor( PoolUser $poolUser, Competition $competition ): Competitor {
        $placeNr = $poolUser->getPool()->getUsers()->count() + 1;
        $competitor = new Competitor( $poolUser, $competition, 1, $placeNr );
        return $competitor;
    }

    public function removeCompetitor() {
    }
}