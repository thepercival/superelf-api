<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Association;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State;
use Sports\Game\Together as TogetherGame;
use Sports\League;
use Sports\Repositories\AgainstGameRepository;
use Sports\Repositories\TogetherGameRepository;
use Sports\Round;
use Sports\Repositories\StructureRepository;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionsCreator;
use SuperElf\Competitor as PoolCompetitor;
use SuperElf\Formation\Editor as FormationEditor;
use SuperElf\League as S11League;
use SuperElf\Periods\Administrator as PeriodAdministrator;
use SuperElf\Points;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;
use SuperElf\PoolCollection;
use SuperElf\Repositories\PoolCollectionRepository as PoolCollectionRepository;
use SuperElf\Repositories\PoolRepository as PoolRepository;
use SuperElf\Sport\Administrator as SportAdministrator;
use SuperElf\User;

final class Administrator
{
    protected CompetitionsCreator $competitionsCreator;
    protected FormationEditor $formationEditor;
    /** @var EntityRepository<PoolUser>  */
    protected EntityRepository $poolUserRepos;
    /** @var EntityRepository<PoolCompetitor>  */
    protected EntityRepository $poolCompetitorRepos;
    /** @var EntityRepository<League>  */
    protected EntityRepository $leagueRepos;
    /** @var EntityRepository<Competition>  */
    protected EntityRepository $competitionRepos;
    /** @var EntityRepository<Points>  */
    protected EntityRepository $pointsRepos;

    public function __construct(
        protected PoolRepository $poolRepos,
        protected PeriodAdministrator $periodAdministrator,
        protected SportAdministrator $sportAdministrator,
        protected PoolCollectionRepository $poolCollectionRepos,
        protected AgainstGameRepository $againstGameRepos,
        protected TogetherGameRepository $togetherGameRepos,
        protected StructureRepository $structureRepos,
        protected ActiveConfigService $activeConfigService,
        protected Configuration $config,
        protected LoggerInterface $logger,
        protected EntityManagerInterface $entityManager
    ) {
        $this->competitionsCreator = new CompetitionsCreator($poolRepos, $structureRepos);
        $this->formationEditor = new FormationEditor($this->config, false);

        $this->poolUserRepos = $entityManager->getRepository(PoolUser::class);
        $this->poolCompetitorRepos = $entityManager->getRepository(PoolCompetitor::class);
        $this->leagueRepos = $entityManager->getRepository(League::class);
        $this->competitionRepos = $entityManager->getRepository(Competition::class);
        $this->pointsRepos = $entityManager->getRepository(Points::class);
    }

    /**
     * @param string $name
     * @param list<S11League> $s11Leagues
     * @return PoolCollection
     * @throws \Exception
     */
    public function createCollection(string $name, array $s11Leagues): PoolCollection
    {
        $poolCollection = $this->poolCollectionRepos->findOneByName($name);
        if ($poolCollection === null) {
            $association = new Association($name);
            $poolCollection = new PoolCollection($association);
            $this->entityManager->persist($poolCollection);
            $this->entityManager->flush();

            foreach ($s11Leagues as $s11League) {
                $league = new League($association, $s11League->name);
                $this->entityManager->persist($league);
                $this->entityManager->flush();
            }
        }
        return $poolCollection;
    }

    public function createPool(CompetitionConfig $competitionConfig, string $name, User|null $user, bool $worldCup = false): Pool
    {
        if( $worldCup ) {
            $s11Leagues = [S11League::WorldCup];
        } else {
            $s11Leagues = [S11League::Competition, S11League::Cup, S11League::SuperCup ];
        }
        $poolCollection = $this->createCollection($name, $s11Leagues);

        $pool = new Pool($poolCollection, $competitionConfig);

        if( $user !== null ) {
            $this->addUser($pool, $user, true);
        }
        $this->entityManager->persist($pool);
        $this->entityManager->flush();

        // $this->createPoolCompetitions($pool);

        return $pool;
    }

    public function addUser(Pool $pool, User $user, bool $admin): PoolUser
    {
        $poolUser = new PoolUser($pool, $user);
        $poolUser->setAdmin($admin);
        return $poolUser;
    }

    public function createCompetitionsCompetitorsStructureAndGames(Pool $pool, S11League|null $filterS11League): void
    {
        $this->checkOnExistingCompetitorsOrStructure($pool, $filterS11League);
        // $sourceStructure = $this->structureRepos->getStructure($pool->getCompetitionConfig()->getSourceCompetition());
        foreach ($this->getS11Leagues($pool, $filterS11League) as $s11League) {
            $creator = $this->competitionsCreator->getCreator($s11League);
            $competition = $this->createPoolCompetition($pool, $s11League);
            if ($competition === null) {
                continue;
            }
            $this->logger->info('   created  "' . $competition->getLeague()->getName() . '"');

            $validPoolUsers = $this->competitionsCreator->getValidPoolUsers($pool, $s11League);
            $newStructure = $creator->createStructure($competition, count($validPoolUsers));
            $this->logger->info('       ' . count($validPoolUsers) . ' valid poolUsers');
            $this->logger->info(
                '       ' . $newStructure->getSingleCategory()->getRootRound()->getNrOfPlaces(
                ) . ' first-round-places created'
            );
            $this->structureRepos->add($newStructure);

            $creator->createGames($newStructure, $pool);
            $this->saveGamesRecursive($newStructure->getSingleCategory()->getRootRound());
            $this->logger->info(
                '       ' . $this->getNrOfGames($newStructure->getSingleCategory()->getRootRound()) . ' games created'
            );

            $poolCompetitors = $creator->createCompetitors($competition, $validPoolUsers, $newStructure);
            foreach ($poolCompetitors as $poolCompetitor) {
                $this->entityManager->persist($poolCompetitor);
                $this->entityManager->flush();
            }
            $this->logger->info('       ' . count($poolCompetitors) . ' competitors created');
        }
        $this->entityManager->persist($pool);
        $this->entityManager->flush();
    }

    /**
     * @param Pool $pool
     * @param S11League|null $filterS11League
     * @return list<S11League>
     */
    private function getS11Leagues(Pool $pool, S11League|null $filterS11League): array {
        if( $pool->getName() === S11League::WorldCup->name ) {
            $s11Leagues = [S11League::WorldCup];
        } else {
            $s11Leagues = [S11League::Competition, S11League::Cup, S11League::SuperCup ];
        }
        if( $filterS11League === null) {
            return $s11Leagues;
        }
        return array_values( array_filter($s11Leagues, function(S11League $s11League) use ($filterS11League): bool {
            return $filterS11League === $s11League;
        }));
    }


    public function createPoolCompetition(Pool $pool, S11League $league): Competition|null
    {
        $sport = $this->sportAdministrator->getSport();
        $competition = $this->competitionsCreator->createCompetition($pool, $sport, $league);
        if ($competition === null) {
            return null;
        }
        $this->entityManager->persist($competition);
        $this->entityManager->flush();
        return $competition;
    }

    protected function checkOnExistingCompetitorsOrStructure(Pool $pool, S11League|null $filterS11League): void
    {
        foreach ($pool->getCompetitions() as $competition) {
            if( $filterS11League !== null && $competition->getLeague()->getName() !== $filterS11League->name) {
                continue;
            }
            if (count($pool->getCompetitors($competition)) > 0) {
                throw new \Exception(
                    'competition "' . $competition->getName() . '" for pool "' . $pool->getName() .
                    '"(' . (string)$pool->getId() . ') already has competitors: use "--replace"',
                    E_ERROR
                );
            }

            if ($this->structureRepos->hasStructure($competition)) {
                throw new \Exception(
                    'competition "' . $competition->getName() . '" for pool "' . $pool->getName() .
                    '"(' . (string)$pool->getId() . ') already has a structure: use "--replace"',
                    E_ERROR
                );
            }
        }
    }

    public function checkOnStartedGames(Pool $pool, S11League|null $filterS11League): void
    {
        foreach ($pool->getCompetitions() as $competition) {
            if( $filterS11League !== null && $competition->getLeague()->getName() !== $filterS11League->name) {
                continue;
            }

            if ($competition->getSingleSport()->createVariant() instanceof AgainstH2h
                || $competition->getSingleSport()->createVariant() instanceof AgainstGpp) {
                $hasAgainstGames = $this->againstGameRepos->hasCompetitionGames(
                    $competition,
                    [State::InProgress, State::Finished]
                );
                if ($hasAgainstGames) {
                    throw new \Exception(
                        'competition "' . $competition->getLeague()->getName() . '" for pool "' . $pool->getName() .
                        '"(' . (string)$pool->getId() . ') already has against games in progress or finished',
                        E_ERROR
                    );
                }
            } else {
                $hasTogetherGames = $this->togetherGameRepos->hasCompetitionGames(
                    $competition,
                    [State::InProgress, State::Finished]
                );
                if ($hasTogetherGames) {
                    throw new \Exception(
                        'competition "' . $competition->getLeague()->getName() . '" for pool "' . $pool->getName() .
                        '"(' . (string)$pool->getId() . ') already has together games in progress or finished',
                        E_ERROR
                    );
                }
            }
        }
    }



//    public function createPoolUsersCompetitionsCompetitorsStructureAndGames(Pool $worldCupPool): void
//    {
//        $this->checkOnStartedGames($worldCupPool);
//        $this->replaceWorldCupPoolUsers($worldCupPool);
//        $this->createCompetitionsCompetitorsStructureAndGames($worldCupPool);
//
//    }


    public function replaceCompetitionsCompetitorsStructureAndGames(Pool $pool, S11League|null $filterS11League): void
    {
        $this->checkOnStartedGames($pool, $filterS11League);
        $this->removeCompetitionsCompetitorsStructureAndGames($pool, $filterS11League);
        $this->createCompetitionsCompetitorsStructureAndGames($pool, $filterS11League);
    }

    public function removeCompetitionsCompetitorsStructureAndGames(Pool $pool, S11League|null $filterS11League): void
    {
        $competitions = $pool->getCompetitions();
        while ($competition = array_pop($competitions)) {
            if( $filterS11League !== null && $competition->getLeague()->getName() !== $filterS11League->name) {
                continue;
            }

            // competition and competitors
            $this->entityManager->remove($competition);
            $this->entityManager->flush();
            // structure and games
            if ($this->structureRepos->hasStructure($competition)) {
                $this->entityManager->remove($competition);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param Pool $worldCupPool
     * @return void
     * @throws \Exception
     */
    public function replaceWorldCupPoolUsers(Pool $worldCupPool): void
    {
        $this->removeWorldCupPoolUsers($worldCupPool);
        $this->copyAndSaveWorldCupPoolUsers($worldCupPool);
    }

    private function removeWorldCupPoolUsers(Pool $worldCupPool): void
    {
        $poolUsers = array_values($worldCupPool->getUsers()->toArray());
        while ($poolUser = array_pop($poolUsers)) {
            $worldCupPool->getUsers()->removeElement($poolUser);
            $this->entityManager->remove($poolUser);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Pool $worldCupPool
     * @return void
     * @throws \Exception
     */
    private function copyAndSaveWorldCupPoolUsers(Pool $worldCupPool): void {
        $originalWorldCupPoolUsers = $this->competitionsCreator->getOriginalValidPoolUsers($worldCupPool);

        // copy
        foreach( $originalWorldCupPoolUsers as $originalWorldCupPoolUser ) {
            $poolUser = new PoolUser( $worldCupPool, $originalWorldCupPoolUser->getUser());
            $this->entityManager->persist($poolUser);
            $this->entityManager->flush();
            $originalWorldCupFormation = $originalWorldCupPoolUser->getAssembleFormation();
            if( $originalWorldCupFormation !== null ) {
                $newFormation = $this->formationEditor->copyFormation($originalWorldCupFormation);
                // $this->poolUserRepos->save($poolUser);
                $poolUser->setAssembleFormation($newFormation);
            }
            $this->entityManager->persist($poolUser);
            $this->entityManager->flush();
        }
    }

    public function copyPoolUserFormationToOtherPool(User $user, Pool $fromPool, Pool $toPool ): void {
        $fromPoolUser = $fromPool->getUser($user);
        if( $fromPoolUser === null ) {
            throw new \Exception('from-poolUser not found');
        }
        $toPoolUser = $toPool->getUser($user);
        if( $toPoolUser === null ) {
            throw new \Exception('to-poolUser not found');
        }
        // remove to-pool-formation
        $fromFormation = $fromPoolUser->getFormation($fromPool->getAssemblePeriod());
        if( $fromFormation === null ) {
            throw new \Exception('from-formation not found');
        }
        $formationEditor = new FormationEditor($this->config, false);
        $toFormation = $formationEditor->copyFormation($fromFormation);
        $toPoolUser->setAssembleFormation($toFormation);
        $this->entityManager->persist($toPoolUser);
        $this->entityManager->flush();
    }



    protected function saveGamesRecursive(Round $round): void
    {
        foreach ($round->getGames() as $game) {
            if ($game instanceof TogetherGame) {
                $this->togetherGameRepos->customSave($game, true);
            } else {
                $this->againstGameRepos->customSave($game, true);
            }
        }
        foreach ($round->getChildren() as $childRound) {
            $this->saveGamesRecursive($childRound);
        }
    }

    protected function getNrOfGames(Round $round): int
    {
        $nrOfGames = count($round->getGames());
        foreach ($round->getChildren() as $childRound) {
            $nrOfGames += $this->getNrOfGames($childRound);
        }
        return $nrOfGames;
    }


}
