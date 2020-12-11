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
use SuperElf\Season\ScoreUnit as SeasonScoreUnit;
use SuperElf\ScoreUnit as BaseScoreUnit;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\Administrator as PeriodAdministrator;
use SuperElf\User;
use SuperElf\Pool\User as PoolUser;
use Sports\Sport\Config\Service as SportConfigService;
use Sports\Sport\Repository as SportRepository;
use SuperElf\ActiveConfig\Service as ActiveConfigService;

class Administrator
{
    protected PoolRepository $poolRepos;
    protected PeriodAdministrator $periodAdministrator;
    protected SportRepository $sportRepos;
    protected Configuration $config;
    protected SportConfigService $sportConfigService;
    protected ActiveConfigService $activeConfigService;
    public const SportName = "superelf";

    public function __construct(
        PoolRepository $poolRepos,
        PeriodAdministrator $periodAdministrator,
        SportRepository $sportRepos,
        ActiveConfigService $activeConfigService,
        Configuration $config) {
        $this->poolRepos = $poolRepos;
        $this->periodAdministrator = $periodAdministrator;
        $this->config = $config;
        $this->sportConfigService = new SportConfigService();
        $this->activeConfigService = $activeConfigService;
        $this->sportRepos = $sportRepos;
    }

    public function createPool(Competition $sourceCompetition, string $name, User $user): Pool
    {
        $poolCollection = new PoolCollection( new Association( $name ) );
        $pool = new Pool( $poolCollection, $sourceCompetition,
                          $this->periodAdministrator->getCreateAndJoinPeriod($sourceCompetition),
                          $this->periodAdministrator->getAssemblePeriod($sourceCompetition),
                          $this->periodAdministrator->getTransferPeriod($sourceCompetition)
        );

        $poolUser = $this->addUser( $pool, $user, true );
        // $this->poolRepos->save( $competition ); // else save per competition
        $this->poolRepos->save( $pool );

        $this->competitionsCreator->create( $pool );
        $this->poolRepos->save( $pool );

        return $pool;
    }

    public function addUser( Pool $pool, User $user, bool $admin ): PoolUser {
        $poolUser = new PoolUser( $pool, $user );
        $poolUser->setAdmin( $admin );
        return $poolUser;
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
