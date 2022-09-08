<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use Selective\Config\Configuration;
use Sports\Association;
use Sports\Competition;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\State;
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
use SuperElf\PoolCollection;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\Sport\Administrator as SportAdministrator;
use SuperElf\User;

class Administrator
{
    protected CompetitionsCreator $competitionsCreator;
    /**
     * @var list<S11League>
     */
    protected array $s11Leagues = [S11League::Competition, S11League::Cup, S11League::SuperCup];

    public function __construct(
        protected PoolRepository $poolRepos,
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
        protected Configuration $config
    ) {
        $this->competitionsCreator = new CompetitionsCreator($structureRepos);
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

    public function createPool(CompetitionConfig $competitionConfig, string $name, User $user): Pool
    {
        $poolCollection = $this->createCollection($name);

        $pool = new Pool($poolCollection, $competitionConfig);

        $this->addUser($pool, $user, true);
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

            $validPoolUsers = $this->competitionsCreator->getValidPoolUsers($pool, $s11League);
            $newStructure = $creator->createStructure($competition, count($validPoolUsers));
            $this->structureRepos->add($newStructure);

            $creator->createGames($newStructure, $pool);
            $this->saveGamesRecursive($newStructure->getSingleCategory()->getRootRound());

            $poolCompetitors = $creator->createCompetitors($competition, $validPoolUsers, $newStructure);
            foreach ($poolCompetitors as $poolCompetitor) {
                $this->poolCompetitorRepos->save($poolCompetitor, true);
            }
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

    protected function checkOnStartedGames(Pool $pool): void
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

    public function replaceCompetitionsCompetitorsStructureAndGames(Pool $pool): void
    {
        $this->checkOnStartedGames($pool);
        $this->removeCompetitionsCompetitorsStructureAndGames($pool);
        $this->createCompetitionsCompetitorsStructureAndGames($pool);
    }

    private function removeCompetitionsCompetitorsStructureAndGames(Pool $pool): void
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

    protected function saveGamesRecursive(Round $round): void
    {
        foreach ($round->getGames() as $game) {
//            if( $game instanceof TogetherGame) {
//                continue;
//            }
            $this->againstGameRepos->customSave($game, true);
        }
        foreach ($round->getChildren() as $childRound) {
            $this->saveGamesRecursive($childRound);
        }
    }
}
