<?php

namespace App\Commands;

use Sports\Season;
use Sports\Team;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Association;
use Sports\League;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Sport\Repository as SportRepository;
use Sports\Association\Repository as AssociationRepository;
use DateTimeInterface;
use LucidFrame\Console\ConsoleTable;
use Psr\Container\ContainerInterface;
use App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Sports\Competition;

class Get extends Command
{
    /**
     * @var SportRepository
     */
    protected $sportRepos;
    /**
     * @var AssociationRepository
     */
    protected $associationRepos;

    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;

    public function __construct(ContainerInterface $container)
    {
        $this->sportRepos = $container->get(SportRepository::class);
        $this->associationRepos = $container->get(AssociationRepository::class);
        $this->competitionRepos = $container->get(CompetitionRepository::class);
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

    protected function execute(InputInterface $input, OutputInterface $output)
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
                } else {
                    echo "objectType \"" . $objectType . "\" kan niet worden opgehaald uit externe bronnen" . PHP_EOL;
                }
            }
        } catch( \Exception $e ) {
            echo $e->getMessage() . PHP_EOL;
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
        $sports = $this->sportRepos->findAll();

        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'Name'));
        foreach( $sports as $sport ) {
            $row = array( $sport->getId(), $sport->getName() );
            $table->addRow( $row );
        }
        $table->display();
    }

    protected function getAssociations(InputInterface $input)
    {
        $associations = $this->associationRepos->findBy( $this->getAssociationFilter($input) );

        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name','parent'));
        uasort( $associations, function( Association $a, Association $b ): int {
            return $a->getName() < $b->getName() ? -1 : 1;
        });
        foreach( $associations as $association ) {
            $row = array( $association->getId(), $association->getName() );
            $parentName = null;
            if( $association->getParent() !== null ) {
                $parentName = $association->getParent()->getName();
            }
            $row[] = $parentName;
            $table->addRow( $row );
        }
        $table->display();
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

        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'start', 'end'));
        foreach( $seasons as $season ) {
            $row = array(
                $season->getId(),
                $season->getName(),
                $season->getStartDateTime()->format( DateTimeInterface::ATOM ),
                $season->getEndDateTime()->format( DateTimeInterface::ATOM )
                );
            $table->addRow( $row );
        }
        $table->display();
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
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'association'));

        uasort( $leagues, function( League $a, League $b ): int {
            if( $a->getAssociation() === $b->getAssociation() ) {
                return $a->getName() < $b->getName() ? -1 : 1;
            }
            return $a->getAssociation()->getName() < $b->getAssociation()->getName() ? -1 : 1;
        });
        foreach( $leagues as $league ) {
            $row = array(
                $league->getId(),
                $league->getName(),
                $league->getAssociation()->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
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

        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'league', 'season', 'startdatetime', 'association'));
        uasort( $competitions, function( Competition $a, Competition $b ): int {
           if( $a->getLeague()->getAssociation() === $b->getLeague()->getAssociation() ) {
               return $a->getLeague()->getName() < $b->getLeague()->getName() ? -1 : 1;
           }
            return $a->getLeague()->getAssociation()->getName() < $b->getLeague()->getAssociation()->getName() ? -1 : 1;
        });
        foreach( $competitions as $competition ) {
            $row = array(
                $competition->getId(),
                $competition->getLeague()->getName(),
                $competition->getSeason()->getName(),
                $competition->getStartDateTime()->format( DateTimeInterface::ATOM ),
                $competition->getLeague()->getAssociation()->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
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
        $competition = $this->competitionRepos->findExt( $league, $season );

        $teamCompetitors = $competition->getTeamCompetitors();
        if( $teamCompetitors->count() === 0 ) {
            echo "no teamcompetitors yet, first fill teamcompetitors" . PHP_EOL;
            return;
        }
        $teams = $competition->getTeamCompetitors()->map( function ( TeamCompetitor $teamCompetitor ): Team {
            return $teamCompetitor->getTeam();
        })->toArray();
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'abbreviation', 'name', 'association'));
        uasort( $teams, function( Team $a, Team $b ): int {
            return $a->getName() < $b->getName() ? -1 : 1;
        });
        /** @var Team $team */
        foreach( $teams as $team ) {
            $row = array(
                $team->getId(),
                $team->getAbbreviation(),
                $team->getName(),
                $team->getAssociation()->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
    }

    protected function showTeamCompetitors(League $league, Season $season)
    {
        $competition = $this->competitionRepos->findExt( $league, $season );

        $teamCompetitors = $competition->getTeamCompetitors()->toArray();

        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'league', 'season', 'pouleNr', 'placeNr', 'team'));
        uasort( $teamCompetitors, function( TeamCompetitor $a, TeamCompetitor $b ): int {
            if( $a->getPouleNr() === $b->getPouleNr() ) {
                return $a->getPlaceNr() < $b->getPlaceNr() ? -1 : 1;
            }
            return $a->getPouleNr() < $b->getPouleNr() ? -1 : 1;
        });
        /** @var TeamCompetitor $teamCompetitor */
        foreach( $teamCompetitors as $teamCompetitor ) {
            $row = array(
                $teamCompetitor->getId(),
                $teamCompetitor->getCompetition()->getLeague()->getName(),
                $teamCompetitor->getCompetition()->getSeason()->getName(),
                $teamCompetitor->getPouleNr(),
                $teamCompetitor->getPlaceNr(),
                $teamCompetitor->getTeam()->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
    }
}
