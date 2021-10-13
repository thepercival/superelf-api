<?php

declare(strict_types=1);

namespace App\Commands;

use App\QueueService;
use App\Commands\ExternalSource as ExternalSourceCommand;
use League\Period\Period;
use Psr\Container\ContainerInterface;
use App\Command;
use Selective\Config\Configuration;

use Sports\Association;
use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Season;
use Sports\Season\Repository as SeasonRepository;
use Sports\Sport;
use SportsImport\ExternalSource\Implementation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\Game\Against as AgainstGameExternalSource;
use SportsImport\ExternalSource\Structure as StructureExternalSource;
use SportsImport\ExternalSource\Competitor\Team as TeamCompetitorExternalSource;
use SportsImport\ExternalSource\Competition as CompetitionExternalSource;
use SportsImport\ExternalSource\Team as TeamExternalSource;
use SportsImport\ExternalSource\League as LeagueExternalSource;
use SportsImport\ExternalSource\Association as AssociationExternalSource;
use SportsImport\ExternalSource\Season as SeasonExternalSource;
use SportsImport\ExternalSource\Sport as SportExternalSource;
use SportsImport\Service as ImportService;

class Import extends ExternalSourceCommand
{
    protected ExternalSourceFactory $externalSourceFactory;
    protected ImportService $importService;

    public function __construct(ContainerInterface $container)
    {
        $this->externalSourceFactory = $container->get(ExternalSourceFactory::class);
        $this->importService = $container->get(ImportService::class);
        parent::__construct($container);
        $this->importService->setEventSender( new QueueService( $this->config->getArray('queue') ) );
    }

    protected function configure(): void
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

    protected function init(InputInterface $input, string $name): void
    {
        $this->initLogger($input, $name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->init($input, 'cron-import');

        $externalSourceName = $input->getArgument('externalSource');
        $externalSourceImpl = $this->externalSourceFactory->createByName($externalSourceName);
        if( $externalSourceImpl === null ) {
            if( $this->logger !== null) {
                $message = "voor '" . $externalSourceName . "' kan er geen externe bron worden gevonden";
                $this->logger->error($message);
            }
            return -1;
        }
        $objectType = $input->getArgument('objectType');

        try {
            if ( $objectType === "sports" and $externalSourceImpl instanceof SportExternalSource) {
                $this->importService->importSports($externalSourceImpl, $externalSourceImpl->getExternalSource());
            } elseif ( $objectType === "seasons" and $externalSourceImpl instanceof SeasonExternalSource) {
                $this->importService->importSeasons($externalSourceImpl, $externalSourceImpl->getExternalSource());
            } else {
                $sport = $this->getSportFromInput($input);
                if ( $objectType === "associations" and $externalSourceImpl instanceof AssociationExternalSource ) {
                    $this->importService->importAssociations($externalSourceImpl, $externalSourceImpl->getExternalSource(), $sport);
                } else {
                    $association = $this->getAssociationFromInput($input);
                    if ( $objectType === "leagues" and $externalSourceImpl instanceof LeagueExternalSource) {
                        $this->importService->importLeagues($externalSourceImpl, $externalSourceImpl->getExternalSource(), $association);
                    } else {
                        $league = $this->getLeagueFromInput($input);
                        $season = $this->getSeasonFromInput($input);
                        if ( $objectType === "competition" and $externalSourceImpl instanceof CompetitionExternalSource) {
                            $this->importService->importCompetition(
                                $externalSourceImpl, $externalSourceImpl->getExternalSource(), $sport, $association, $league, $season);
                        } elseif ( $objectType === "teams" and $externalSourceImpl instanceof TeamExternalSource) {
                            $this->importService->importTeams(
                                $externalSourceImpl, $externalSourceImpl->getExternalSource(), $sport, $association, $league, $season);
                        } elseif ( $objectType === "teamcompetitors" and $externalSourceImpl instanceof TeamCompetitorExternalSource) {
                            $this->importService->importTeamCompetitors(
                                $externalSourceImpl, $externalSourceImpl->getExternalSource(), $sport, $association, $league, $season);
                        } elseif ( $objectType === "structure"  and $externalSourceImpl instanceof StructureExternalSource) {
                            $this->importService->importStructure(
                                $externalSourceImpl, $externalSourceImpl->getExternalSource(), $sport, $association, $league, $season);
                        } elseif ( $objectType === "schedule"  and $externalSourceImpl instanceof AgainstGameExternalSource) {
                            $this->importService->importSchedule(
                                $externalSourceImpl, $externalSourceImpl->getExternalSource(), $sport, $association, $league, $season);
                        } elseif ( $objectType === "gamedetails" and $externalSourceImpl instanceof AgainstGameExternalSource) {
                            $this->importAgainstGameDetails(
                                $externalSourceImpl, $externalSourceImpl, $sport, $association, $league, $season);
                        } elseif ( $objectType === "images" ) {
                            $this->importImages($externalSourceImpl, $league, $season);
                        } else {
                            if( $this->logger !== null) {
                                $message = "objectType \"" . $objectType . "\" kan niet worden geimporteerd uit externe bronnen";
                                $this->logger->error($message);
                            }
                        }
                    }
                }
            }
        } catch( \Exception $e ) {
            if( $this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

//    protected function importSports(Implementation $externalSourceImpl): void
//    {
//        $this->importService->importSports($externalSourceImpl);
//    }
//
//    protected function importAssociations(Implementation $externalSourceImpl, Sport $sport): void
//    {
//        $this->importService->importAssociations($externalSourceImpl, $sport);
//    }
//
//    protected function importSeasons(Implementation $externalSourceImpl): void
//    {
//        $this->importService->importSeasons($externalSourceImpl);
//    }
//
//    protected function importLeagues(Implementation $externalSourceImpl, Association $association): void
//    {
//        $this->importService->importLeagues($externalSourceImpl, $association);
//    }
//
//    protected function importCompetition(Implementation $externalSourceImpl,
//        Sport $sport, Association $association, League $league, Season $season): void
//    {
//        $this->importService->importCompetition($externalSourceImpl, $sport, $association, $league, $season );
//    }

    protected function importAgainstGameDetails(
        AgainstGameExternalSource $againstGameExternalSource,
        Implementation $externalSourceImpl,
        Sport $sport, Association $association, League $league, Season $season): void
    {
        // bepaal de period waarin gezocht moet worden
        // voor de cronjob is 24, 3 en 2 uur na de start van de wedstrijd


        $period = new Period(
            new \DateTimeImmutable('2020-10-18 12:29'),
            new \DateTimeImmutable('2020-10-18 12:31') ); // klaiber
        // HIER VERDER
        /*$period = new Period(
            new \DateTimeImmutable('2020-09-01 08:00'),
            new \DateTimeImmutable('2020-09-21 08:00') );
        $period = new Period(
            new \DateTimeImmutable('2020-09-21 08:00'),
            new \DateTimeImmutable('2020-10-16 08:00') );
        $period = new Period(
            new \DateTimeImmutable('2020-10-16 08:00'),
            new \DateTimeImmutable('2020-10-19 08:00') );*/
        $period = new Period(
            new \DateTimeImmutable('2020-10-19 08:00'),
            new \DateTimeImmutable('2020-12-11 08:00') );
        $this->importService->importAgainstGameDetails(
            $againstGameExternalSource, $externalSourceImpl->getExternalSource(),
            $sport, $association, $league, $season, $period );
    }

    protected function importImages(Implementation $externalSourceImpl, League $league, Season $season): void
    {
        $localPath = $this->config->getString('www.apiurl-localpath');
        $localPath .= $this->config->getString('images.personsSuffix');
        $publicPath = $this->config->getString('www.apiurl');
        $publicPath .= $this->config->getString('images.personsSuffix');
        $maxWidth = 150;
        $this->importService->importPersonImages($externalSourceImpl, $league, $season, $localPath, $publicPath, $maxWidth );

        $localPath = $this->config->getString('www.apiurl-localpath');
        $localPath .= $this->config->getString('images.teamsSuffix');
        $publicPath = $this->config->getString('www.apiurl');
        $publicPath .= $this->config->getString('images.teamsSuffix');
        $maxWidth = 150;
        $this->importService->importTeamImages($externalSourceImpl, $league, $season, $localPath, $publicPath, $maxWidth );
    }
}
