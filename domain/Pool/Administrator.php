<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use SuperElf\Sport\Administrator as SportAdministrator;
use Selective\Config\Configuration;
use Sports\Association;
use Sports\Competition;
use Sports\Sport;
use SportsHelpers\GameMode;
use SuperElf\CompetitionsCreator;
use SuperElf\PoolCollection;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Period\Administrator as PeriodAdministrator;
use SuperElf\User;
use SuperElf\Pool\User as PoolUser;
use Sports\Sport\Repository as SportRepository;
use SuperElf\ActiveConfig\Service as ActiveConfigService;

class Administrator
{
    protected CompetitionsCreator $competitionsCreator;

    public function __construct(
        protected PoolRepository $poolRepos,
        protected PeriodAdministrator $periodAdministrator,
        protected SportAdministrator $sportAdministrator,
        protected ActiveConfigService $activeConfigService,
        protected Configuration $config) {
        $this->competitionsCreator = new CompetitionsCreator();
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

        $competitionTypes = $this->sportAdministrator->getCompetitionTypes($pool);
        $this->competitionsCreator->createCompetitions( $pool, $competitionTypes );
        $this->poolRepos->save( $pool );

        return $pool;
    }

    public function addUser( Pool $pool, User $user, bool $admin ): PoolUser {
        $poolUser = new PoolUser( $pool, $user );
        $poolUser->setAdmin( $admin );
        return $poolUser;
    }
}
