<?php

declare(strict_types=1);

namespace App\Commands;

use Sports\Game;
use Sports\Season;
use Sports\Team;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\League;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Game\Repository as GameRepository;
use Sports\Structure\Repository as StructureRepository;
use Psr\Container\ContainerInterface;
use App\Command;
use Sports\Output\ConsoleTable;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class Get extends Command
{

    protected CompetitionRepository $competitionRepos;
    protected StructureRepository $structureRepos;
    protected GameRepository $gameRepos;

    public function __construct(ContainerInterface $container)
    {
        $this->competitionRepos = $container->get(CompetitionRepository::class);
        $this->structureRepos = $container->get(StructureRepository::class);
        $this->gameRepos = $container->get(GameRepository::class);
        parent::__construct($container);
    }

    protected function configure()
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

    protected function init(InputInterface $input, string $name)
    {
        $this->initLogger($input, $name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->init($input, 'cron-get');

        $objectType = $input->getArgument('objectType');

        try {
            if ( $objectType === "sports" ) {
                $this->getSports($input);
            } elseif ( $objectType === "associations" ) {
                $this->getAssociations($input);
            } else if ( $objectType === "seasons" ) {
                $this->showSeasons($input);
            } elseif ( $objectType === "leagues" ) {
                $this->showLeagues($input);
            } elseif ( $objectType === "competitions" ) {
                $this->showCompetitions($input);
            } else {
                $league = $this->getLeagueFromInput($input);
                $season = $this->getSeasonFromInput($input);
                if ( $objectType === "teams" ) {
                    $this->showTeams($league, $season );
                } elseif ( $objectType === "teamcompetitors" ) {
                    $this->showTeamCompetitors($league, $season);
                } elseif ( $objectType === "structure" ) {
                    $this->showStructure($league, $season);
                } elseif ( $objectType === "games" ) {
                    $this->showGames($league, $season);
                } elseif ( $objectType === "game" ) {
                    $this->showGame($league, $season, $this->getIdFromInput($input) );
                } else {
                    if( $this->logger !== null) {
                        $message = "objectType \"" . $objectType . "\" kan niet worden opgehaald uit bronnen";
                        $this->logger->error($message);
                    }
                }
            }
        } catch( \Exception $e ) {
            if( $this->logger !== null) {
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
    


    protected function getSports(InputInterface $input)
    {
        $table = new ConsoleTable\Sports();
        $table->display( $this->sportRepos->findAll() );
    }

    protected function getAssociations(InputInterface $input)
    {
        $associations = $this->associationRepos->findBy( $this->getAssociationFilter($input) );

        $table = new ConsoleTable\Associations();
        $table->display( $associations );
    }

    protected function getAssociationFilter( InputInterface $input ): array {
        if( strlen( $input->getOption("filter") ) === 0 ) {
            return [];
        }
        $filterAsStdClass = json_decode( $input->getOption("filter") );
        if( $filterAsStdClass === null ) {
            return [];
        }

        if( property_exists($filterAsStdClass,"name") ) {
            return ["name" => $filterAsStdClass->name ];
        }
        return [];
    }

    protected function showSeasons(InputInterface $input )
    {
        $seasons = $this->seasonRepos->findBy( $this->getSeasonFilter($input) );

        $table = new ConsoleTable\Seasons();
        $table->display( $seasons );
    }

    protected function getSeasonFilter( InputInterface $input ): array {
        if( strlen( $input->getOption("filter") ) === 0 ) {
            return [];
        }
        $filterAsStdClass = json_decode( $input->getOption("filter") );
        if( $filterAsStdClass === null ) {
            return [];
        }

        if( property_exists($filterAsStdClass,"name") ) {
            return ["name" => $filterAsStdClass->name ];
        }
        return [];
    }

    protected function showLeagues(InputInterface $input)
    {
        $leagues = $this->leagueRepos->findBy( $this->getLeagueFilter($input) );

        $table = new ConsoleTable\Leagues();
        $table->display( $leagues );
    }

    protected function getLeagueFilter( InputInterface $input ): array {
        if( strlen( $input->getOption("filter") ) === 0 ) {
            return [];
        }
        $filterAsStdClass = json_decode( $input->getOption("filter") );
        if( $filterAsStdClass === null ) {
            return [];
        }

        if( property_exists($filterAsStdClass,"name") ) {
            return ["name" => $filterAsStdClass->name ];
        }
        return [];
    }

    protected function showCompetitions(InputInterface $input)
    {
        $competitions = $this->competitionRepos->findBy( $this->getCompetitionFilter($input) );

        $table = new ConsoleTable\Competitions();
        $table->display( $competitions );
    }

    protected function getCompetitionFilter( InputInterface $input ): array {
        if( strlen( $input->getOption("filter") ) === 0 ) {
            return [];
        }
        $filterAsStdClass = json_decode( $input->getOption("filter") );
        if( $filterAsStdClass === null ) {
            return [];
        }
        return [];
    }

    protected function showTeams(League $league, Season $season)
    {
        $competition = $this->competitionRepos->findOneExt( $league, $season );
        if( $competition === null ) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $teamCompetitors = $competition->getTeamCompetitors();
        if( $teamCompetitors->count() === 0 ) {
            echo "no teamcompetitors yet, first fill teamcompetitors" . PHP_EOL;
            return;
        }
        $teams = $competition->getTeamCompetitors()->map( function ( TeamCompetitor $teamCompetitor ): Team {
            return $teamCompetitor->getTeam();
        })->toArray();
        $table = new ConsoleTable\Teams();
        $table->display( $teams );
    }

    protected function showTeamCompetitors(League $league, Season $season)
    {
        $competition = $this->competitionRepos->findOneExt( $league, $season );
        if( $competition === null ) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $teamCompetitors = $competition->getTeamCompetitors()->toArray();

        $table = new ConsoleTable\TeamCompetitors();
        $table->display( $teamCompetitors );
    }

    protected function showStructure(League $league, Season $season)
    {
        $competition = $this->competitionRepos->findOneExt( $league, $season );
        if( $competition === null ) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $structure = $this->structureRepos->getStructure( $competition );
        if( $structure === null ) {
            throw new \Exception("no structure found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $teamCompetitors = $competition->getTeamCompetitors()->toArray();
        $table = new ConsoleTable\Structure();
        $table->display( $competition, $structure, $teamCompetitors );
    }

    protected function showGames(League $league, Season $season)
    {
        $competition = $this->competitionRepos->findOneExt( $league, $season );
        if( $competition === null ) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $structure = $this->structureRepos->getStructure( $competition );
        if( $structure === null ) {
            throw new \Exception("no structure found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $games = $structure->getFirstRoundNumber()->getGames( Game::ORDER_BY_BATCH );

        $teamCompetitors = $competition->getTeamCompetitors()->toArray();
        $table = new ConsoleTable\Games();
        $table->display( $competition, $games, $teamCompetitors );
    }

    protected function showGame(League $league, Season $season, $id )
    {
        $competition = $this->competitionRepos->findOneExt( $league, $season );
        if( $competition === null ) {
            throw new \Exception("no competition found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $structure = $this->structureRepos->getStructure( $competition );
        if( $structure === null ) {
            throw new \Exception("no structure found for league '".$league->getName()."' and season '".$season->getName()."'", E_ERROR);
        }
        $game = $this->gameRepos->find( $id );
        if( $game === null ) {
            throw new \Exception("no game found for league '".$league->getName()."' and season '".$season->getName()."' and id " . $id , E_ERROR);
        }

        $teamCompetitors = $competition->getTeamCompetitors()->toArray();
        $table = new ConsoleTable\Game();
        $table->display( $competition, $game, $teamCompetitors );
    }
}
