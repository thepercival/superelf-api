<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Container\ContainerInterface;
use Sports\Association;
use Sports\Repositories\CompetitionRepository;
use Sports\Competitor\StartLocationMap;
use Sports\Game;
use Sports\Game\Against as AgainstGame;
use Sports\Repositories\AgainstGameRepository;
use Sports\Game\Together as TogetherGame;
use Sports\League;
use Sports\Output\ConsoleTable;
use Sports\Season;
use Sports\Structure\NameService as StructureNameService;
use Sports\Repositories\StructureRepository;
use Sports\Team;
use SportsHelpers\SportRange;
use SportsImport\Attachers\AgainstGameAttacher;
use SportsImport\Entity;
use SportsImport\ExternalSource\SofaScore;
use SportsImport\ExternalSource;
use SportsImport\Repositories\AttacherRepository;
use stdClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:get games-basics --sport=football --league=Eredivisie --season=2022/2023 --gameRoundRange=21-21
 * php bin/console.php app:get game --sport=football --league=Eredivisie --season=2022/2023 --id=181
 */
final class Get extends Command
{
    use EntityTrait;

    protected CompetitionRepository $competitionRepos;
    protected StructureRepository $structureRepos;
    protected AgainstGameRepository $againstGameRepos;
    /** @var AttacherRepository<AgainstGameAttacher>  */
    protected AttacherRepository $againstGameAttacherRepos;
    /** @var EntityRepository<ExternalSource>  */
    protected EntityRepository $externalSourceRepos;
    /** @var EntityRepository<Team>  */
    protected EntityRepository $teamRepos;
    protected EntityManagerInterface $entityManager;

    public function __construct(ContainerInterface $container)
    {
        /** @var EntityManagerInterface entityManager */
        $this->entityManager = $container->get(EntityManagerInterface::class);

        /** @var CompetitionRepository $competitionRepos */
        $competitionRepos = $container->get(CompetitionRepository::class);
        $this->competitionRepos = $competitionRepos;

        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        $metadata = $this->entityManager->getClassMetadata(AgainstGameAttacher::class);
        $this->againstGameAttacherRepos = new AttacherRepository($this->entityManager, $metadata);

        $this->externalSourceRepos = $this->entityManager->getRepository(ExternalSource::class);

        /** @var StructureRepository $structureRepos */
        $structureRepos = $container->get(StructureRepository::class);
        $this->structureRepos = $structureRepos;

        $this->teamRepos = $this->entityManager->getRepository(Team::class);

        parent::__construct($container);
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:get')
            // the short description shown while running "php bin/console list"
            ->setDescription('gets the objects')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('get the objects');

        $this->addArgument('objectType', InputArgument::REQUIRED, 'for example associations or competitions');

        $this->addOption('sport', null, InputOption::VALUE_OPTIONAL, 'the name of the sport');
        $this->addOption('association', null, InputOption::VALUE_OPTIONAL, 'the name of the association');
        $this->addOption('league', null, InputOption::VALUE_OPTIONAL, 'the name of the league');
        $this->addOption('season', null, InputOption::VALUE_OPTIONAL, 'the name of the season');
        $this->addOption('gameRoundRange', null, InputOption::VALUE_OPTIONAL, '1-4');
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'game-id');
        $this->addOption('externalSource', null, InputOption::VALUE_OPTIONAL, SofaScore::NAME);
        $this->addOption('filter', null, InputOption::VALUE_OPTIONAL, 'the json filter');

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-get');

        $entity = $this->getEntityFromInput($input);

        try {
            if ($entity === Entity::SPORTS) {
                $this->showSports($input);
            } elseif ($entity === Entity::ASSOCIATIONS) {
                $this->showAssociations($input);
            } elseif ($entity === Entity::SEASONS) {
                $this->showSeasons($input);
            } elseif ($entity === Entity::LEAGUES) {
                $this->showLeagues($input);
            } elseif ($entity === Entity::COMPETITIONS) {
                $this->showCompetitions($input);
            } elseif ($entity === Entity::TEAMS) {
                $association = $this->inputHelper->getAssociationFromInput($input);
                $this->showTeams($association);
            } else {
                $league = $this->inputHelper->getLeagueFromInput($input);
                $season = $this->inputHelper->getSeasonFromInput($input);
                if ($entity === Entity::TEAMCOMPETITORS) {
                    $this->showTeamCompetitors($league, $season);
                } elseif ($entity === Entity::STRUCTURE) {
                    $this->showStructure($league, $season);
                } elseif ($entity === Entity::GAMES_BASICS) {
                    $gameRoundRange = $this->inputHelper->getGameRoundNrRangeFromInput($input);
                    $this->showAgainstGames($league, $season, $gameRoundRange);
                } elseif ($entity === Entity::GAME) {
                    $this->showAgainstGame($league, $season, $this->inputHelper->getIdFromInput($input));
                } else {
                    $message = 'objectType "' . $entity . '" kan niet worden opgehaald uit bronnen';
                    $this->getLogger()->error($message);
                }
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }

//        if ($input->getOption("structures")) {
//            $this->importStructures(SofaScore::NAME);
//        }
//        if ($input->getOption("games")) {
//            $this->importGames(SofaScore::NAME);
//        }

        return 0;
    }



    protected function showSports(InputInterface $input): void
    {
        $table = new ConsoleTable\Sports();
        $table->display($this->sportRepos->findAll());
    }

    protected function showAssociations(InputInterface $input): void
    {
        $associations = $this->associationRepos->findBy($this->getInputFilter($input));

        $table = new ConsoleTable\Associations();
        $table->display($associations);
    }

    protected function showSeasons(InputInterface $input): void
    {
        $seasons = $this->seasonRepos->findBy($this->getInputFilter($input));

        $table = new ConsoleTable\Seasons();
        $table->display($seasons);
    }

    protected function showLeagues(InputInterface $input): void
    {
        $leagues = $this->leagueRepos->findBy($this->getInputFilter($input));

        $table = new ConsoleTable\Leagues();
        $table->display($leagues);
    }

    protected function showCompetitions(InputInterface $input): void
    {
        $competitions = $this->competitionRepos->findBy($this->getInputFilter($input));

        $table = new ConsoleTable\Competitions();
        $table->display($competitions);
    }

    /**
     * @param InputInterface $input
     * @return array<string, mixed>
     */
    protected function getInputFilter(InputInterface $input): array
    {
        $inputFilterAsArray = [];

        /** @var string|null $inputFilter */
        $inputFilter = $input->getOption("filter");
        if (!is_string($inputFilter) || strlen($inputFilter) === 0) {
            return $inputFilterAsArray;
        }
        /** @var stdClass|null $inputFilterClass */
        $inputFilterClass = json_decode($inputFilter);
        if ($inputFilterClass === null) {
            return $inputFilterAsArray;
        }

        if (property_exists($inputFilterClass, "name")) {
            $inputFilterAsArray["name"] = (string)$inputFilterClass->name;
        }
        return $inputFilterAsArray;
    }

    protected function showTeams(Association $association): void
    {
        $teams = $this->teamRepos->findBy(['association' => $association]);
        $table = new ConsoleTable\Teams();
        $table->display($teams);
    }

    protected function showTeamCompetitors(League $league, Season $season): void
    {
        $competition = $this->competitionRepos->findOneExt($league, $season);
        if ($competition === null) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $teamCompetitors = array_values($competition->getTeamCompetitors()->toArray());

        $table = new ConsoleTable\TeamCompetitors();
        $table->display($teamCompetitors);
    }

    protected function showStructure(League $league, Season $season): void
    {
        $competition = $this->competitionRepos->findOneExt($league, $season);
        if ($competition === null) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $structure = $this->structureRepos->getStructure($competition);
        $teamCompetitors = array_values($competition->getTeamCompetitors()->toArray());
        $table = new ConsoleTable\Structure();
        $table->display($competition, $structure, $teamCompetitors);
    }

    protected function showAgainstGames(League $league, Season $season, SportRange|null $gameRoundRange = null): void
    {
        $competition = $this->competitionRepos->findOneExt($league, $season);
        if ($competition === null) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $structure = $this->structureRepos->getStructure($competition);
        $games = $structure->getFirstRoundNumber()->getGames(Game\Order::ByBatch);
        $againstGames = array_filter($games, function (AgainstGame|TogetherGame $game): bool {
            return $game instanceof AgainstGame;
        });
        $againstGames = array_filter($againstGames, function (AgainstGame $game) use ($gameRoundRange): bool {
            return $gameRoundRange === null || $gameRoundRange->isWithIn($game->getGameRoundNumber());
        });

        $teamCompetitors = array_values($competition->getTeamCompetitors()->toArray());
        $table = new ConsoleTable\AgainstGames();
        $table->display($competition, array_values($againstGames), $teamCompetitors);
    }

    protected function showAgainstGame(League $league, Season $season, string|int|false $id): void
    {
        if ($id === false) {
            throw new \Exception("no id in input", E_ERROR);
        }
        $competition = $this->competitionRepos->findOneExt($league, $season);
        if ($competition === null) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $this->structureRepos->getStructure($competition);
        $againstGame = $this->againstGameRepos->find($id);
        if ($againstGame === null) {
            throw new \Exception(
                "no game found for league '" . $league->getName() . "' and season '" . $season->getName(
                ) . "' and id " . $id, E_ERROR
            );
        }

        $teamCompetitors = array_values($competition->getTeamCompetitors()->toArray());
        $structureNameService = new StructureNameService(new StartLocationMap($teamCompetitors));
        $table = new ConsoleTable\AgainstGame();
        $table->display($competition, $againstGame, $structureNameService);

        $externalSources = $this->externalSourceRepos->findAll();
        foreach ($externalSources as $externalSource) {
            $externalId = $this->againstGameAttacherRepos->findOneByImportable($externalSource, $againstGame)?->getExternalId();
            if ($externalId !== null) {
                $this->getLogger()->info('externalSource "' . $externalSource->getName() . '" => ' . $externalId);
            }
        }
    }
}
