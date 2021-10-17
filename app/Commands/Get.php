<?php
declare(strict_types=1);

namespace App\Commands;

use Sports\Association;
use Sports\Game;
use Sports\Season;
use SportsHelpers\SportRange;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\League;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Structure\Repository as StructureRepository;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Team\Repository as TeamRepository;
use Psr\Container\ContainerInterface;
use App\Command;
use Sports\Output\ConsoleTable;

use SportsImport\Entity;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;

class Get extends ExternalSource
{
    protected CompetitionRepository $competitionRepos;
    protected StructureRepository $structureRepos;
    protected AgainstGameRepository $againstGameRepos;
    protected TeamRepository $teamRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var CompetitionRepository competitionRepos */
        $this->competitionRepos = $container->get(CompetitionRepository::class);
        /** @var AgainstGameRepository againstGameRepos */
        $this->againstGameRepos = $container->get(AgainstGameRepository::class);
        /** @var StructureRepository structureRepos */
        $this->structureRepos = $container->get(StructureRepository::class);
        /** @var TeamRepository teamRepos */
        $this->teamRepos = $container->get(TeamRepository::class);

        parent::__construct($container, 'command-get');
    }

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

        $this->addOption('filter', null, InputOption::VALUE_OPTIONAL, 'the json filter');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLoggerFromInput($input);

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
                $association = $this->getAssociationFromInput($input);
                $this->showTeams($association);
            } else {
                $league = $this->getLeagueFromInput($input);
                $season = $this->getSeasonFromInput($input);
                if ($entity === Entity::TEAMCOMPETITORS) {
                    $this->showTeamCompetitors($league, $season);
                } elseif ($entity === Entity::STRUCTURE) {
                    $this->showStructure($league, $season);
                } elseif ($entity === Entity::GAMES ) {
                    $gameRoundRange = $this->getGameRoundNrRangeFromInput($input);
                    $this->showAgainstGames($league, $season, $gameRoundRange);
                } elseif ($entity === Entity::GAMEDETAILS) {
                    $this->showAgainstGame($league, $season, $this->getIdFromInput($input));
                } else {
                    $message = 'objectType "'. $entity . '" kan niet worden opgehaald uit bronnen';
                    $this->logger->error($message);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
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

        /** @var string|null $competitionFilter */
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
        $table->display(array_values($teams));
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
        $games = array_values($structure->getFirstRoundNumber()->getGames(Game\Order::ByBatch));
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

    protected function showAgainstGame(League $league, Season $season, string|int $id): void
    {
        $competition = $this->competitionRepos->findOneExt($league, $season);
        if ($competition === null) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $this->structureRepos->getStructure($competition);
        $againstGame = $this->againstGameRepos->find($id);
        if ($againstGame === null) {
            throw new \Exception("no game found for league '".$league->getName()."' and season '".$season->getName()."' and id " . $id, E_ERROR);
        }

        $teamCompetitors = array_values($competition->getTeamCompetitors()->toArray());
        $table = new ConsoleTable\AgainstGame();
        $table->display($competition, $againstGame, $teamCompetitors);
    }
}
