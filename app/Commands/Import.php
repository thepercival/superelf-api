<?php

namespace App\Commands;

use Psr\Container\ContainerInterface;
use App\Command;
use Selective\Config\Configuration;

use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Season;
use Sports\Season\Repository as SeasonRepository;
use SportsImport\ExternalSource\Implementation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\SofaScore;
use SportsImport\Service as ImportService;

class Import extends Command
{
    protected ExternalSourceFactory $externalSourceFactory;
    protected ImportService $importService;

    public function __construct(ContainerInterface $container)
    {
        $this->externalSourceFactory = $container->get(ExternalSourceFactory::class);
        $this->importService = $container->get(ImportService::class);
        parent::__construct($container);
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:import')
            // the short description shown while running "php bin/console list"
            ->setDescription('imports the objects')
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
        $this->init($input, 'cron-import');

        $externalSourceName = $input->getArgument('externalSource');
        $externalSourceImpl = $this->externalSourceFactory->createByName($externalSourceName);
        if( $externalSourceImpl === null ) {
            echo "voor '" . $externalSourceName . "' kan er geen externe bron worden gevonden" . PHP_EOL;
            return -1;
        }

        $objectType = $input->getArgument('objectType');

        try {
            if ( $objectType === "sports" ) {
                $this->importSports($externalSourceImpl);
            } elseif ( $objectType === "associations" ) {
                $this->importAssociations($externalSourceImpl);
            } elseif ( $objectType === "seasons" ) {
                $this->importSeasons($externalSourceImpl);
            } elseif ( $objectType === "leagues" ) {
                $this->importLeagues($externalSourceImpl);
            } else {
                $league = $this->getLeagueFromInput($input);
                $season = $this->getSeasonFromInput($input);
                if ( $objectType === "competition" ) {
                    $this->importCompetition($externalSourceImpl, $league, $season);
                } elseif ( $objectType === "teams" ) {
                    $this->importTeams($externalSourceImpl, $league, $season);
                } elseif ( $objectType === "teamcompetitors" ) {
                    $this->importTeamCompetitors($externalSourceImpl, $league, $season);
                } elseif ( $objectType === "structure" ) {
                    $this->importStructure($externalSourceImpl, $league, $season);
                } elseif ( $objectType === "games" ) {
                    $this->importGames($externalSourceImpl, $league, $season);
                } else {
                    echo "objectType \"" . $objectType . "\" kan niet worden geimporteerd uit externe bronnen" . PHP_EOL;
                }
            }
        } catch( \Exception $e ) {
            echo $e->getMessage() . PHP_EOL;
        }


        return 0;
    }

    protected function importSports(Implementation $externalSourceImpl)
    {
        $this->importService->importSports($externalSourceImpl);
    }

    protected function importAssociations(Implementation $externalSourceImpl)
    {
        $this->importService->importAssociations($externalSourceImpl);
    }

    protected function importSeasons(Implementation $externalSourceImpl)
    {
        $this->importService->importSeasons($externalSourceImpl);
    }

    protected function importLeagues(Implementation $externalSourceImpl)
    {
        $this->importService->importLeagues($externalSourceImpl);
    }

    protected function importCompetition(Implementation $externalSourceImpl, League $league, Season $season)
    {
        $this->importService->importCompetition($externalSourceImpl, $league, $season );
    }

    protected function importTeams(Implementation $externalSourceImpl, League $league, Season $season)
    {
        $this->importService->importTeams($externalSourceImpl, $league, $season);
    }

    protected function importTeamCompetitors(Implementation $externalSourceImpl, League $league, Season $season)
    {
        $this->importService->importTeamCompetitors($externalSourceImpl, $league, $season);
    }

    protected function importStructure(Implementation $externalSourceImpl, League $league, Season $season)
    {
        $this->importService->importStructure($externalSourceImpl, $league, $season);
    }

    protected function importGames(Implementation $externalSourceImpl, League $league, Season $season)
    {
        $this->importService->importGames($externalSourceImpl, $league, $season);
    }
}
