<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Association;
use Sports\Competition;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\State;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Together\Repository as TogetherGameRepository;
use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Round;
use Sports\Structure\Repository as StructureRepository;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionsCreator;
use SuperElf\Competitor\Repository as CompetitorRepository;
use SuperElf\Competitor\Repository as PoolCompetitorRepsitory;
use SuperElf\League as S11League;
use SuperElf\Periods\Administrator as PeriodAdministrator;
use SuperElf\Points\Repository as PointsRepository;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Formation\Editor as FormationEditor;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\PoolCollection;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\Sport\Administrator as SportAdministrator;
use SuperElf\User;

class Administrator
{
    protected CompetitionsCreator $competitionsCreator;
    protected FormationEditor $formationEditor;
    /**
     * @var list<S11League>
     */
    protected array $s11Leagues = [S11League::Competition, S11League::Cup, S11League::SuperCup, S11League::WorldCup];

    public function __construct(
        protected PoolRepository $poolRepos,
        protected PoolUserRepository $poolUserRepos,
        protected PointsRepository $pointsRepository,
        protected PeriodAdministrator $periodAdministrator,
        protected SportAdministrator $sportAdministrator,
        protected PoolCollectionRepository $poolCollectionRepos,
        protected PoolCompetitorRepsitory $poolCompetitorRepos,
        protected LeagueRepository $leagueRepos,
        protected CompetitionRepository $competitionRepos,
        protected CompetitorRepository $competitorRepos,
        protected StructureRepository $structureRepos,
        protected AgainstGameRepository $againstGameRepos,
        protected TogetherGameRepository $togetherGameRepos,
        protected ActiveConfigService $activeConfigService,
        protected Configuration $config,
        protected LoggerInterface $logger
    ) {
        $this->competitionsCreator = new CompetitionsCreator($structureRepos);
        $this->formationEditor = new FormationEditor($this->config, false);
    }

    public function createCollection(string $name): PoolCollection
    {
        $poolCollection = $this->poolCollectionRepos->findOneByName($name);
        if ($poolCollection === null) {
            $association = new Association($name);
            $poolCollection = new PoolCollection($association);
            $this->poolCollectionRepos->save($poolCollection);

            foreach ($this->s11Leagues as $s11League) {
                $league = new League($association, $s11League->name);
                $this->leagueRepos->save($league, true);
            }
        }
        return $poolCollection;
    }

    public function createPool(CompetitionConfig $competitionConfig, string $name, User|null $user): Pool
    {
        $poolCollection = $this->createCollection($name);

        $pool = new Pool($poolCollection, $competitionConfig);

        if( $user !== null ) {
            $this->addUser($pool, $user, true);
        }
        $this->poolRepos->save($pool, true);

        // $this->createPoolCompetitions($pool);

        return $pool;
    }

    public function addUser(Pool $pool, User $user, bool $admin): PoolUser
    {
        $poolUser = new PoolUser($pool, $user);
        $poolUser->setAdmin($admin);
        return $poolUser;
    }

    public function createCompetitionsCompetitorsStructureAndGames(Pool $pool): void
    {
        $this->checkOnExistingCompetitorsOrStructure($pool);
        // $sourceStructure = $this->structureRepos->getStructure($pool->getCompetitionConfig()->getSourceCompetition());
        foreach ($this->s11Leagues as $s11League) {
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
                $this->poolCompetitorRepos->save($poolCompetitor, true);
            }
            $this->logger->info('       ' . count($poolCompetitors) . ' competitors created');
        }
        $this->poolRepos->save($pool);
    }

    public function createPoolCompetition(Pool $pool, S11League $league): Competition|null
    {
        $sport = $this->sportAdministrator->getSport();
        $competition = $this->competitionsCreator->createCompetition($pool, $sport, $league);
        if ($competition === null) {
            return null;
        }
        $this->competitionRepos->save($competition, true);
        return $competition;
    }

    protected function checkOnExistingCompetitorsOrStructure(Pool $pool): void
    {
        foreach ($pool->getCompetitions() as $competition) {
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

    public function checkOnStartedGames(Pool $pool): void
    {
        foreach ($pool->getCompetitions() as $competition) {
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


    public function replaceCompetitionsCompetitorsStructureAndGames(Pool $pool): void
    {
        $this->checkOnStartedGames($pool);
        $this->removeCompetitionsCompetitorsStructureAndGames($pool);
        $this->createCompetitionsCompetitorsStructureAndGames($pool);
    }

    public function removeCompetitionsCompetitorsStructureAndGames(Pool $pool): void
    {
        $competitions = $pool->getCompetitions();
        while ($competition = array_pop($competitions)) {
            // competition and competitors
            $this->competitionRepos->remove($competition);
            // structure and games
            if ($this->structureRepos->hasStructure($competition)) {
                $this->structureRepos->remove($competition);
            }
        }
    }

    /**
     * @param Pool $worldCupPool
     * @param list<PoolUser> $originalWorldCupPoolUsers
     * @return void
     * @throws \Exception
     */
    public function replaceWorldCupPoolUsers(Pool $worldCupPool, array $originalWorldCupPoolUsers): void
    {
        $this->removeWorldCupPoolUsers($worldCupPool);
        $this->copyAndSaveWorldCupPoolUsers($worldCupPool, $originalWorldCupPoolUsers);
    }

    private function removeWorldCupPoolUsers(Pool $worldCupPool): void
    {
        $poolUsers = array_values($worldCupPool->getUsers()->toArray());
        while ($poolUser = array_pop($poolUsers)) {
            $worldCupPool->getUsers()->removeElement($poolUser);
            $this->poolUserRepos->remove($poolUser);
        }
    }

    /**
     * @param Pool $worldCupPool
     * @param list<PoolUser> $originalWorldCupPoolUsers
     * @return void
     * @throws \Exception
     */
    private function copyAndSaveWorldCupPoolUsers(Pool $worldCupPool, array $originalWorldCupPoolUsers): void {
        foreach( $originalWorldCupPoolUsers as $originalWorldCupPoolUser ) {
            $poolUser = new PoolUser( $worldCupPool, $originalWorldCupPoolUser->getUser());
            $this->poolUserRepos->save($poolUser);
            $originalWorldCupFormation = $originalWorldCupPoolUser->getAssembleFormation();
            if( $originalWorldCupFormation !== null ) {
                $newFormation = $this->formationEditor->copyFormation($originalWorldCupFormation);
                // $this->poolUserRepos->save($poolUser);
                $poolUser->setAssembleFormation($newFormation);
            }
            $this->poolUserRepos->save($poolUser);
        }
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
