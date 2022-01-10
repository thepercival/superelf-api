<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use Selective\Config\Configuration;
use Sports\Association;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\League\Repository as LeagueRepository;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionsCreator;
use SuperElf\Period\Administrator as PeriodAdministrator;
use SuperElf\Points\Creator as PointsCreator;
use SuperElf\Points\Repository as PointsRepository;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\PoolCollection;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\Sport\Administrator as SportAdministrator;
use SuperElf\User;

class Administrator
{
    protected CompetitionsCreator $competitionsCreator;
    protected PointsCreator $pointsCreator;

    public function __construct(
        protected PoolRepository $poolRepos,
        protected PointsRepository $pointsRepository,
        protected PeriodAdministrator $periodAdministrator,
        protected SportAdministrator $sportAdministrator,
        protected PoolCollectionRepository $poolCollectionRepos,
        protected LeagueRepository $leagueRepos,
        protected CompetitionRepository $competitionRepos,
        protected ActiveConfigService $activeConfigService,
        protected Configuration $config
    ) {
        $this->competitionsCreator = new CompetitionsCreator();
        $this->pointsCreator = new PointsCreator();
    }

    public function createCollection(string $name): PoolCollection
    {
        $poolCollection = $this->poolCollectionRepos->findOneByName($name);
        if ($poolCollection === null) {
            $poolCollection = new PoolCollection(new Association($name));
            $this->poolCollectionRepos->save($poolCollection);
        }
        return $poolCollection;
    }

    public function createPool(CompetitionConfig $competitionConfig, string $name, User $user): Pool
    {
        $poolCollection = $this->createCollection($name);

//        $competitionConfig = new CompetitionConfig(
//            $sourceCompetition,
//            $this->pointsCreator->get($sourceCompetition->getSeason()),
//            $this->periodAdministrator->getCreateAndJoinPeriod($sourceCompetition),
//            $this->periodAdministrator->getAssemblePeriod($sourceCompetition),
//            $this->periodAdministrator->getTransferPeriod($sourceCompetition)
//        );

        $pool = new Pool($poolCollection, $competitionConfig);

        $this->addUser($pool, $user, true);
        $this->poolRepos->save($pool, true);

        $competitionTypes = $this->sportAdministrator->getCompetitionTypes($pool);
        $sport = $this->sportAdministrator->getSport();
        $competitions = $this->competitionsCreator->createCompetitions($pool, $sport, $competitionTypes);

        $association = $pool->getCollection()->getAssociation();
        // because association(through poolcollection) already exists, doctrine gives error
        foreach ($competitions as $competition) {
            $association->getLeagues()->removeElement($competition->getLeague());
        }
        foreach ($competitions as $competition) {
            $this->competitionRepos->save($competition);
        }
        $this->poolRepos->save($pool);
        // undo removal
        foreach ($competitions as $competition) {
            $association->getLeagues()->add($competition->getLeague());
        }
        return $pool;
    }

    public function addUser(Pool $pool, User $user, bool $admin): PoolUser
    {
        $poolUser = new PoolUser($pool, $user);
        $poolUser->setAdmin($admin);
        return $poolUser;
    }
}
