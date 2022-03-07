<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use Selective\Config\Configuration;
use Sports\Association;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\League\Repository as LeagueRepository;
use Sports\Structure\Repository as StructureRepository;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionsCreator;
use SuperElf\Competitor\Repository as PoolCompetitorRepsitory;
use SuperElf\Period\Administrator as PeriodAdministrator;
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

    public function __construct(
        protected PoolRepository $poolRepos,
        protected PointsRepository $pointsRepository,
        protected PeriodAdministrator $periodAdministrator,
        protected SportAdministrator $sportAdministrator,
        protected PoolCollectionRepository $poolCollectionRepos,
        protected PoolCompetitorRepsitory $poolCompetitorRepos,
        protected LeagueRepository $leagueRepos,
        protected CompetitionRepository $competitionRepos,
        protected StructureRepository $structureRepos,
        protected ActiveConfigService $activeConfigService,
        protected Configuration $config
    ) {
        $this->competitionsCreator = new CompetitionsCreator();
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

        $pool = new Pool($poolCollection, $competitionConfig);

        $this->addUser($pool, $user, true);
        $this->poolRepos->save($pool, true);

        $sport = $this->sportAdministrator->getSport();
        $competitions = $this->competitionsCreator->createCompetitions($pool, $sport);

        // @TODO CDK CHECK SECOND SEASON
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

    public function removeAndCreateStructureAndCompetitors(Pool $pool): void
    {
        throw new \Exception('implement remove', E_ERROR);
// //         remove with repositories
// //         remove competitors and than create them
// //
// //        foreach ($competitions as $competition) {
// //            // -------- REMOVE ----------- //
// //            $competitors = $pool->getCompetitors($competition);
// //            while ($competitor = array_pop($competitors)) {
// //                $this->competitorRepos->remove($competitor);
// //            }
// //        }


//        $sourceStructure = $this->structureRepos->getStructure($pool->getCompetitionConfig()->getSourceCompetition());
//        foreach ($pool->getCompetitions() as $competition) {
//
//            // ---- REMOVE COMPETITORS -------
//            foreach ($pool->getUsers() as $poolUser) {
//                $competitor = $poolUser->getCompetitor($competition);
//                if ($competitor === null) {
//                    continue;
//                }
//                $poolUser->getCompetitors()->removeElement($competitor);
//                $this->poolCompetitorRepos->remove($competitor);
//            }
//
//            // ---- ADD COMPETITORS, REMOVE AND ADD STRUCTURE -------
//            $s11League = $pool->getLeague($competition);
//            $creator = $this->competitionsCreator->getCreator($s11League);
//            $newStructure = $creator->createStructureAndCompetitors($pool, $sourceStructure);
////            save competitors, structure and games with repos????
//            $competitors = $pool->getCompetitors($competition);
//
//            foreach ($competitors as $competitor) {
//                $this->poolCompetitorRepos->save($competitor);
//            }
//            $this->structureRepos->removeAndAdd($competition, $newStructure);
//        }
//        $this->poolRepos->save($pool);
    }
}
