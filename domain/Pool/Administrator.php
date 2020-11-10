<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use DateTimeImmutable;
use Selective\Config\Configuration;
use Sports\Association;
use Sports\Competition;
use Sports\League;
use Sports\Season;
use Sports\Sport;
use SuperElf\Competitor;
use SuperElf\PoolCollection;
use SuperElf\Pool;
use SuperElf\Pool\ScoreUnit as PoolScoreUnit;
use SuperElf\ScoreUnit as BaseScoreUnit;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\Period as PoolPeriod;
use League\Period\Period as BasePeriod;
use SuperElf\User;
use SuperElf\Pool\User as PoolUser;
use Sports\Sport\Config\Service as SportConfigService;
use Sports\Sport\Repository as SportRepository;
use SuperElf\ActiveConfig\Service as ActiveConfigService;

class Administrator
{
    protected PoolRepository $poolRepos;
    protected SportRepository $sportRepos;
    protected Configuration $config;
    protected SportConfigService $sportConfigService;
    protected ActiveConfigService $activeConfigService;
    public const SportName = "superelf";

    public function __construct(
        PoolRepository $poolRepos,
        SportRepository $sportRepos,
        ActiveConfigService $activeConfigService,
        Configuration $config) {
        $this->poolRepos = $poolRepos;
        $this->config = $config;
        $this->sportConfigService = new SportConfigService();
        $this->activeConfigService = $activeConfigService;
        $this->sportRepos = $sportRepos;
    }

    public function createPool(Season $season, Competition $sourceCompetition, string $name, User $user): Pool
    {
        $poolCollection = new PoolCollection( new Association( $name ) );
        $pool = new Pool( $poolCollection, $season, $sourceCompetition );
        $this->addDefaultScoreUnits( $pool );
        $this->addPeriods( $pool );

        $defaultLeagueName = $poolCollection->getLeagueName( PoolCollection::LEAGUE_DEFAULT );
        $competition = new Competition( new League( $poolCollection->getAssociation(), $defaultLeagueName), $season );
        $competition->setStartDateTime( $season->getStartDateTime() );
        $this->sportConfigService->createDefault( $this->getSport(), $competition );

        $poolUser = $this->addUser( $pool, $user, true );
        $this->poolRepos->save( $competition );
        $this->poolRepos->save( $pool );

        $competitor = $this->addCompetitor( $poolUser );

        return $pool;
    }

    protected function addDefaultScoreUnits( Pool $pool ) {
        foreach( $this->config->getArray("scoreunits" ) as $number => $points ) {
            new PoolScoreUnit( $pool, new BaseScoreUnit( $number ), (int) $points );
        }
    }

    protected function addPeriods( Pool $pool ) {
        $transfersStart = new DateTimeImmutable( $this->config->getString('periods.transfersStart' ) );
        $transfersEnd = new DateTimeImmutable( $this->config->getString('periods.transfersEnd' ) );

        $createAndJoinPeriod = $this->activeConfigService->getActiveCreateAndJoinPeriod();
        $joinAndChoosePlayersPeriod = $this->activeConfigService->getActiveJoinAndChoosePlayersPeriod();
        $transferPeriod = new BasePeriod( $transfersStart, $transfersEnd );

        new PoolPeriod( $pool, $createAndJoinPeriod, PoolPeriod::CREATE_AND_JOIN );
        new PoolPeriod( $pool, $joinAndChoosePlayersPeriod, PoolPeriod::CHOOSE_PLAYERS );
        new PoolPeriod( $pool, $transferPeriod, PoolPeriod::TRANSFER );
    }

    public function addUser( Pool $pool, User $user, bool $admin ): PoolUser {
        $poolUser = new PoolUser( $pool, $user );
        $poolUser->setAdmin( $admin );
        return $poolUser;
    }

    public function addCompetitor( PoolUser $poolUser, Competition $competition = null ): Competitor {
        if ( $competition === null ) {
            $competition = $poolUser->getPool()->getCompetition();
        }
        $placeNr = $poolUser->getPool()->getUsers()->count() + 1;
        $competitor = new Competitor( $poolUser, $competition, 1, $placeNr );
        $this->poolRepos->save( $competitor );
        return $competitor;
    }

    public function removeCompetitor() {

    }

    protected function getSport(): Sport {
        $sport = $this->sportRepos->findOneBy( ["name" => self::SportName] );
        if( $sport === null ) {
            $sport = new Sport( self::SportName );
            $sport->setTeam( false );
            $this->sportRepos->save( $sport );
        }
        return $sport;
    }
}
