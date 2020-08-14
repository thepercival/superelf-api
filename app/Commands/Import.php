<?php

namespace App\Commands;

use Psr\Container\ContainerInterface;
use App\Command;
use Selective\Config\Configuration;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\SofaScore;
use SportsImport\Service as ImportService;

class Import extends Command
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
            ->setName('app:import')
            // the short description shown while running "php bin/console list"
            ->setDescription('imports the objects')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('import the objects');

        $this->addOption('sports', null, InputOption::VALUE_NONE, 'sports');
        $this->addOption('associations', null, InputOption::VALUE_NONE, 'associations');
        $this->addOption('seasons', null, InputOption::VALUE_NONE, 'seasons');
        $this->addOption('leagues', null, InputOption::VALUE_NONE, 'leagues');
        $this->addOption('competitions', null, InputOption::VALUE_NONE, 'competitions');
        $this->addOption('teams', null, InputOption::VALUE_NONE, 'teams');
        $this->addOption('teamcompetitors', null, InputOption::VALUE_NONE, 'teamcompetitors');
        $this->addOption('structures', null, InputOption::VALUE_NONE, 'structure');
        $this->addOption('games', null, InputOption::VALUE_NONE, 'games');

        parent::configure();
    }

    protected function init(InputInterface $input, string $name)
    {
        $this->initLogger($input, $name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, 'cron-import');

        if ($input->getOption("sports")) {
            $this->importSports(SofaScore::NAME);
        }
        if ($input->getOption("associations")) {
            $this->importAssociations(SofaScore::NAME);
        }
        if ($input->getOption("seasons")) { // input manual
            $this->importSeasons(SofaScore::NAME);
        }
        if ($input->getOption("leagues")) {
            $this->importLeagues(SofaScore::NAME);
        }
        if ($input->getOption("competitions")) {
            $this->importCompetitions(SofaScore::NAME);
        }
        if ($input->getOption("teams")) {
            $this->importTeams(SofaScore::NAME);
        }
        if ($input->getOption("teamcompetitors")) {
            $this->importTeamCompetitors(SofaScore::NAME);
        }
        if ($input->getOption("structures")) {
            $this->importStructures(SofaScore::NAME);
        }
        if ($input->getOption("games")) {
            $this->importGames(SofaScore::NAME);
        }
        return 0;
    }

    protected function importSports(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importSports($externalSourcImpl);
    }

    protected function importAssociations(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importAssociations($externalSourcImpl);
    }

    protected function importSeasons(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importSeasons($externalSourcImpl);
    }

    protected function importLeagues(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importLeagues($externalSourcImpl);
    }

    protected function importCompetitions(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importCompetitions($externalSourcImpl);
    }

    protected function importTeams(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importTeams($externalSourcImpl);
    }

    protected function importTeamCompetitors(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importTeamCompetitors($externalSourcImpl);
    }

    protected function importStructures(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importStructures($externalSourcImpl);
    }

    protected function importGames(string $externalSourceName)
    {
        $externalSourcImpl = $this->externalSourceFactory->createByName($externalSourceName);
        $this->importService->importGames($externalSourcImpl);
    }
}
