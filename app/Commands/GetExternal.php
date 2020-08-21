<?php

namespace App\Commands;

use Sports\Season;
use SportsImport\ExternalSource\ApiHelper;
use SportsImport\ExternalSource\CacheInfo;
use Sports\Output\ConsoleTable;
use Sports\League;
use Sports\Competition\Repository as CompetitionRepository;
use SportsImport\Attacher\Competition\Repository as CompetitionAttacherRepository;
use Psr\Container\ContainerInterface;
use App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource;
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
                } elseif ( $objectType === "structure" ) {
                    $this->getStructure($externalSourceImpl, $league, $season);
                } elseif ( $objectType === "games" ) {
                    $startBatchNr = $this->getStartBatchNrFromInput($input);
                    $this->getGames($externalSourceImpl, $league, $season, $startBatchNr);
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
        $table = new ConsoleTable\Sports();
        $table->display( $externalSourceImpl->getSports() );
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_SPORTS );
    }

    protected function getAssociations(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Association ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen bonden opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable\Associations();
        $table->display( $externalSourceImpl->getAssociations() );
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_ASSOCIATIONS );
    }

    protected function getSeasons(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Season ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen seizoenen opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable\Seasons();
        $table->display( $externalSourceImpl->getSeasons() );

        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_SEASONS );
    }

    protected function getLeagues(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\League ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen competities opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable\Leagues();
        $table->display( $externalSourceImpl->getLeagues() );
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_LEAGUES );
    }

    protected function getCompetitions(ExternalSource\Implementation $externalSourceImpl)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        $table = new ConsoleTable\Competitions();
        $table->display( $externalSourceImpl->getCompetitions() );
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
        $table = new ConsoleTable\Teams();
        $table->display( $externalSourceImpl->getTeams($competition) );
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_TEAMS );
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
        $table = new ConsoleTable\TeamCompetitors();
        $table->display( $externalSourceImpl->getTeamCompetitors($competition) );
        $this->showMetadata( $externalSourceImpl, ExternalSource::DATA_TEAMCOMPETITORS );
    }

    protected function getStructure(ExternalSource\Implementation $externalSourceImpl, League $league, Season $season)
    {
        if( !($externalSourceImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourceImpl instanceof ExternalSource\Competitor\Team ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen deelnemers opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourceImpl instanceof ExternalSource\Structure ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen structuur opvragen" . PHP_EOL;
            return;
        }

        $competition = $this->importService->getExternalCompetitionByLeagueAndSeason( $externalSourceImpl, $league, $season );
        if( $competition === null ) {
            throw new \Exception("no external compettion found for league " . $league->getName() . " and season " . $season->getName(), E_ERROR );
        }
        $teamCompetitors = $externalSourceImpl->getTeamCompetitors($competition);
        $table = new ConsoleTable\Structure();
        $table->display( $competition, $externalSourceImpl->getStructure($competition), $teamCompetitors );

    }

    protected function getGames(ExternalSource\Implementation $externalSourceImpl, League $league, Season $season, int $startBatchNr )
    {
        if( !($externalSourceImpl instanceof ExternalSource\Competition ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen competitieseizoenen opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourceImpl instanceof ExternalSource\Structure ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen structuur opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourceImpl instanceof ExternalSource\Competitor\Team ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen deelnemers opvragen" . PHP_EOL;
            return;
        }
        if( !($externalSourceImpl instanceof ExternalSource\Game ) ) {
            echo "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName() . "\" kan geen wedstrijden opvragen" . PHP_EOL;
            return;
        }

        $competition = $this->importService->getExternalCompetitionByLeagueAndSeason( $externalSourceImpl, $league, $season );
        if( $competition === null ) {
            throw new \Exception("no external compettion found for league " . $league->getName() . " and season " . $season->getName(), E_ERROR );
        }

        $batchNrs = $externalSourceImpl->getBatchNrs($competition);
        $games = [];
        $endBatchNr = $startBatchNr + 3; // show 4 batches
        for( $batchNr = $startBatchNr ; $batchNr <= $endBatchNr ; $batchNr++ ) {
            if (count(
                    array_filter(
                        $batchNrs,
                        function (int $batchNrIt) use ($batchNr): bool {
                            return $batchNrIt === $batchNr;
                        }
                    )
                ) === 0) {
                $this->logger->info("batchnr " . $batchNr . " komt niet voor in de externe bron");
            }
            $games = array_merge($games, $externalSourceImpl->getGames($competition, $batchNr));
        }
        $teamCompetitors = $externalSourceImpl->getTeamCompetitors($competition);
        $table = new ConsoleTable\Games();
        $table->display( $competition, $games, $teamCompetitors );
    }


}
