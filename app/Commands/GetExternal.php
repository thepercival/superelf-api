<?php

namespace App\Commands;

use Sports\Season;
use SportsImport\ExternalSource\ApiHelper;
use SportsImport\ExternalSource\CacheInfo;
use Sports\Game;
use Sports\Association;
use Sports\League;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Season\Repository as SeasonRepository;
use SportsImport\Attacher\Competition\Repository as CompetitionAttacherRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use LucidFrame\Console\ConsoleTable;
use Psr\Container\ContainerInterface;
use App\Command;
use Selective\Config\Configuration;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource;
use Sports\Competition;
use SportsImport\Service as ImportService;

class GetExternal extends Command
{
    protected ExternalSourceFactory $externalSourceFactory;
    protected ImportService $importService;
    protected CompetitionRepository $competitionRepos;
    protected CompetitionAttacherRepository $competitionAttacherRepos;

    public function __construct(ContainerInterface $container)
    {
        $this->externalSourceFactory = $container->get(ExternalSourceFactory::class);
        $this->importService = $container->get(ImportService::class);
        $this->competitionRepos = $container->get(CompetitionRepository::class);
        $this->competitionAttacherRepos = $container->get(CompetitionAttacherRepository::class);
        parent::__construct($container);
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:getexternal')
            // the short description shown while running "php bin/console list"
            ->setDescription('gets the external objects')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('import the objects');

        $this->addArgument('externalSource', InputArgument::REQUIRED, 'for example sofascore');
        $this->addArgument('objectType', InputArgument::REQUIRED, 'for example associations or competitions');

        parent::configure();
    }

    protected function init(InputInterface $input, string $name)
    {
        $this->initLogger($input, $name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, 'cron-getexternal');

        $externalSourceName = $input->getArgument('externalSource');
        $externalSourceImpl = $this->externalSourceFactory->createByName($externalSourceName);
        if( $externalSourceImpl === null ) {
            echo "voor \"" . $externalSourceName . "\" kan er geen externe bron worden gevonden" . PHP_EOL;
            return -1;
        }

        $objectType = $input->getArgument('objectType');

        try {
            if ( $objectType === "sports" ) {
                $this->getSports($externalSourceImpl);
            } elseif ( $objectType === "associations" ) {
                $this->getAssociations($externalSourceImpl);
            } elseif ( $objectType === "seasons" ) {
                $this->getSeasons($externalSourceImpl);
            } elseif ( $objectType === "leagues" ) {
                $this->getLeagues($externalSourceImpl);
            } elseif ( $objectType === "competitions" ) {
                $this->getCompetitions($externalSourceImpl);
            } else {
                $league = $this->getLeagueFromInput($input);
                $season = $this->getSeasonFromInput($input);
                if ( $objectType === "teams" ) {
                    $this->getTeams($externalSourceImpl, $league, $season);
                } elseif ( $objectType === "teamcompetitors" ) {
                    $this->getTeamCompetitors($externalSourceImpl, $league, $season);
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
    
    protected function showMetadata( ExternalSource\Implementation $externalSourceImpl, int $dataType ) {
        if( $externalSourceImpl instanceof CacheInfo ) {
            $this->logger->info( $externalSourceImpl->getCacheInfo( $dataType ) );
        }
        if( $externalSourceImpl instanceof ApiHelper ) {
            $this->logger->info( "endpoint: " . $externalSourceImpl->getEndPoint( $dataType ) );
        }
    }

    protected function getSports(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Sport ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen sporten opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name'));
        foreach( $externalSourceImpl->getSports() as $sport ) {
            $row = array( $sport->getId(), $sport->getName() );
            $table->addRow( $row );
        }
        $table->display();
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_SPORTS );
    }

    protected function getAssociations(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Association ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen bonden opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name','parent'));
        $associations = $externalSourceImpl->getAssociations();
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
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_ASSOCIATIONS );
    }

    protected function getSeasons(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Season ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen seizoenen opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'start', 'end'));
        foreach( $externalSourceImpl->getSeasons() as $season ) {
            $row = array(
                $season->getId(),
                $season->getName(),
                $season->getStartDateTime()->format( DateTimeInterface::ATOM ),
                $season->getEndDateTime()->format( DateTimeInterface::ATOM )
                );
            $table->addRow( $row );
        }
        $table->display();
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_SEASONS );
    }

    protected function getLeagues(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\League ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen competities opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'association'));
        $leagues = $externalSourceImpl->getLeagues();
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
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_LEAGUES );
    }

    protected function getCompetitions(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'league', 'season', 'startdatetime', 'association'));
        $competitions = $externalSourceImpl->getCompetitions();
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
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_COMPETITIONS );
    }

    protected function getTeams(ExternalSource\Implementation $externalSourceImpl, League $league, Season $season )
    {
        if( !($externalSourceImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourceImpl instanceof ExternalSource\Team ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen deelnemers opvragen" . PHP_EOL;
            return;
        }
        $competition = $this->importService->getExternalCompetitionByLeagueAndSeason( $externalSourceImpl, $league, $season );
        if( $competition === null ) {
            throw new \Exception("no external compettion found for league " . $league->getName() . " and season " . $season->getName(), E_ERROR );
        }

        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'abbreviation', 'competition'));
        foreach( $externalSourceImpl->getTeams($competition) as $team ) {
            $row = array(
                $team->getId(),
                $team->getName(),
                $team->getAbbreviation(),
                $team->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
    }
    
    protected function getTeamCompetitors(ExternalSource\Implementation $externalSourceImpl, League $league, Season $season)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourceImpl instanceof ExternalSource\Competitor\Team ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen deelnemers opvragen" . PHP_EOL;
            return;
        }
        $competition = $this->importService->getExternalCompetitionByLeagueAndSeason( $externalSourceImpl, $league, $season );
        if( $competition === null ) {
            throw new \Exception("no external compettion found for league " . $league->getName() . " and season " . $season->getName(), E_ERROR );
        }
        $teamCompetitors = $externalSourceImpl->getTeamCompetitors($competition);
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
