<?php
declare(strict_types=1);

namespace SuperElf\Pool;

use SuperElf\Sport\Administrator as SportAdministrator;
use Selective\Config\Configuration;
use Sports\Association;
use Sports\Competition;
use SuperElf\CompetitionsCreator;
use SuperElf\PoolCollection;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Period\Administrator as PeriodAdministrator;
use SuperElf\User;
use SuperElf\Pool\User as PoolUser;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use Sports\League\Repository as LeagueRepository;
use Sports\Competition\Repository as CompetitionRepository;
use SuperElf\ActiveConfig\Service as ActiveConfigService;

class Administrator
{
    protected CompetitionsCreator $competitionsCreator;

    public function __construct(
        protected PoolRepository $poolRepos,
        protected PeriodAdministrator $periodAdministrator,
        protected SportAdministrator $sportAdministrator,
        protected PoolCollectionRepository $poolCollectionRepos,
        protected LeagueRepository $leagueRepos,
        protected CompetitionRepository $competitionRepos,
        protected ActiveConfigService $activeConfigService,
        protected Configuration $config
    ) {
        $this->competitionsCreator = new CompetitionsCreator();
    }

    public function createCollection(string $name): PoolCollection
    {
        $poolCollection = $this->poolCollectionRepos->findOneByName($name);
        if( $poolCollection === null) {
            $poolCollection = new PoolCollection(new Association($name));
            $this->poolCollectionRepos->save($poolCollection);
        }
        return $poolCollection;
    }

    public function createPool(Competition $sourceCompetition, string $name, User $user): Pool
    {
        $poolCollection = $this->createCollection($name);
        $pool = new Pool(
            $poolCollection,
            $sourceCompetition,
            $this->periodAdministrator->getCreateAndJoinPeriod($sourceCompetition),
            $this->periodAdministrator->getAssemblePeriod($sourceCompetition),
            $this->periodAdministrator->getTransferPeriod($sourceCompetition)
        );

        $this->addUser($pool, $user, true);
        $this->poolRepos->save($pool);

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
