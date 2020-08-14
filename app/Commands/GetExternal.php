<?php

namespace App\Commands;

use SuperElf\LayBack\Output;
use Sports\Game;
use Sports\NameService;
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
    /**
     * @var ExternalSourceFactory
     */
    protected $externalSourceFactory;
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var ImportService
     */
    protected $importService;

    public function __construct(ContainerInterface $container)
    {
        $this->externalSourceFactory = $container->get(ExternalSourceFactory::class);
        $this->importService = $container->get(ImportService::class);
        $this->container = $container;
        parent::__construct($container->get(Configuration::class));
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
        $this->addArgument('objectType', InputArgument::REQUIRED, 'for example associations or comopetitions');

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
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        if( $externalSourcImpl === null ) {
            echo "voor \"" . $externalSourceName . "\" kan er geen externe bron worden gevonden" . PHP_EOL;
            return -1;
        }

        $objectType = $input->getArgument('objectType');

        if ( $objectType === "sports" ) {
            $this->getSports($externalSourcImpl);
        } elseif ( $objectType === "associations" ) {
            $this->getAssociations($externalSourcImpl);
        } elseif ( $objectType === "seasons" ) {
            $this->getSeasons($externalSourcImpl);
        } elseif ( $objectType === "leagues" ) {
            $this->getLeagues($externalSourcImpl);
        } elseif ( $objectType === "competitions" ) {
            $this->getCompetitions($externalSourcImpl);
        } elseif ( $objectType === "teams" ) {
            $this->getTeams($externalSourcImpl);
        } elseif ( $objectType === "teamcompetitors" ) {
            $this->getTeamCompetitors($externalSourcImpl);
        } else {
            echo "objectType \"" . $objectType . "\" kan niet worden opgehaald uit externe bronnen" . PHP_EOL;
        }

//        if ($input->getOption("structures")) {
//            $this->importStructures(SofaScore::NAME);
//        }
//        if ($input->getOption("games")) {
//            $this->importGames(SofaScore::NAME);
//        }
//        if ($input->getOption("laybacks")) {
//            $this->importLayBacks([Betfair::NAME]);
//        }
        return 0;
    }

    protected function getSports(ExternalSource\Implementation $externalSourcImpl)
    {
        if( !($externalSourcImpl instanceof ExternalSource\Sport ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen sporten opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'Name'));
        foreach( $externalSourcImpl->getSports() as $sport ) {
            $row = array( $sport->getId(), $sport->getName() );
            $table->addRow( $row );
        }
        $table->display();
    }

    protected function getAssociations(ExternalSource\Implementation $externalSourcImpl)
    {
        if( !($externalSourcImpl instanceof ExternalSource\Association ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen bonden opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'Name','Parent'));
        foreach( $externalSourcImpl->getAssociations() as $association ) {
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

    protected function getSeasons(ExternalSource\Implementation $externalSourcImpl)
    {
        if( !($externalSourcImpl instanceof ExternalSource\Season ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen seizoenen opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'Name', 'Start', 'End'));
        foreach( $externalSourcImpl->getSeasons() as $season ) {
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

    protected function getLeagues(ExternalSource\Implementation $externalSourcImpl)
    {
        if( !($externalSourcImpl instanceof ExternalSource\League ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen competities opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'Name', 'Association'));
        foreach( $externalSourcImpl->getLeagues() as $league ) {
            $row = array(
                $league->getId(),
                $league->getName(),
                $league->getAssociation()->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
    }

    protected function getCompetitions(ExternalSource\Implementation $externalSourcImpl)
    {
        if( !($externalSourcImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'League', 'Season', 'StartDateTime', 'Association'));
        foreach( $externalSourcImpl->getCompetitions() as $competition ) {
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

    protected function getTeams(ExternalSource\Implementation $externalSourcImpl)
    {
        if( !($externalSourcImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourcImpl instanceof ExternalSource\Team ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen deelnemers opvragen" . PHP_EOL;
            return;
        }
        $getCompetitionTeams = function(Competition $competition) use ($externalSourcImpl): void {
            $table = new ConsoleTable();
            $table->setHeaders(array('Id', 'Name', 'Abbreviation', 'Competition'));
            foreach( $externalSourcImpl->getTeams($competition) as $team ) {
                $row = array(
                    $team->getId(),
                    $team->getName(),
                    $team->getAbbreviation(),
                    $team->getName()
                );
                $table->addRow( $row );
            }
            $table->display();
        };
        foreach( $externalSourcImpl->getCompetitions() as $competition ) {
            $getCompetitionTeams($competition);
        }
    }
    
    protected function getTeamCompetitors(ExternalSource\Implementation $externalSourcImpl)
    {
        if( !($externalSourcImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourcImpl instanceof ExternalSource\Competitor\Team ) ) {
            echo "de externe bron \"" . $externalSourcImpl->getExternalSource()->getName() . "\" kan geen deelnemers opvragen" . PHP_EOL;
            return;
        }
        $getCompetitionTeamCompetitors = function(Competition $competition) use ($externalSourcImpl): void {
            $table = new ConsoleTable();
            $table->setHeaders(array('Id', 'Name', 'Abbreviation', 'Competition'));
            foreach( $externalSourcImpl->getTeamCompetitors($competition) as $teamCompetitor ) {
                $row = array(
                    $teamCompetitor->getId(),
                    $teamCompetitor->getName()
                );
                $table->addRow( $row );
            }
            $table->display();
        };
        foreach( $externalSourcImpl->getCompetitions() as $competition ) {
            $getCompetitionTeamCompetitors($competition);
        }
    }
}
