<?php
declare(strict_types=1);

namespace App\Commands\ExternalSource;

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
use SportsImport\Entity;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\Implementation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\Competitions;
use SportsImport\ExternalSource\CompetitionStructure;
use SportsImport\ExternalSource\CompetitionDetails;
use SportsImport\Importer;

class Import extends ExternalSourceCommand
{
    protected Importer $importer;

    public function __construct(ContainerInterface $container)
    {
        /** @var Importer importer */
        $this->importer = $container->get(Importer::class);
        parent::__construct($container, 'command-import');
        $this->importer->setEventSender(new QueueService($this->config->getArray('queue')));
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLoggerFromInput($input);
        $externalSourceName = (string)$input->getArgument('externalSource');
        $externalSourceImpl = $this->externalSourceFactory->createByName($externalSourceName);
        if ($externalSourceImpl === null) {
            $message = "voor '" . $externalSourceName . "' kan er geen externe bron worden gevonden";
            $this->logger->error($message);
            return -1;
        }

        $entity = $this->getEntityFromInput($input);

        try {
            if ($externalSourceImpl instanceof Competitions) {
                switch ($entity) {
                    case Entity::SPORTS:
                        $this->importer->importSports($externalSourceImpl, $externalSourceImpl->getExternalSource());
                        return 0;
                    case Entity::SEASONS:
                        $this->importer->importSeasons($externalSourceImpl, $externalSourceImpl->getExternalSource());
                        return 0;
                    case Entity::ASSOCIATIONS:
                        $sport = $this->getSportFromInput($input);
                        $this->importer->importAssociations($externalSourceImpl, $externalSourceImpl->getExternalSource(), $sport);
                        return 0;
                    case Entity::LEAGUES:
                        $sport = $this->getSportFromInput($input);
                        $association = $this->getAssociationFromInput($input);
                        $this->importer->importLeagues(
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $association
                        );
                        return 0;
                    case Entity::COMPETITIONS:
                        $sport = $this->getSportFromInput($input);
                        $league = $this->getLeagueFromInput($input);
                        $season = $this->getSeasonFromInput($input);
                        $this->importer->importCompetition(
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season
                        );
                        return 0;
                }
            }
            if ($externalSourceImpl instanceof Competitions &&
                $externalSourceImpl instanceof CompetitionStructure) {
                $sport = $this->getSportFromInput($input);
                $league = $this->getLeagueFromInput($input);
                $season = $this->getSeasonFromInput($input);
                switch ($entity) {
                    case Entity::TEAMS:
                        $this->importer->importTeams(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season
                        );
                        return 0;
                    case Entity::TEAMCOMPETITORS:
                        $this->importer->importTeamCompetitors(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season
                        );
                        return 0;
                    case Entity::STRUCTURE:
                        $this->importer->importStructure(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season
                        );
                        return 0;
                }
            }
            if ($externalSourceImpl instanceof Competitions &&
                $externalSourceImpl instanceof CompetitionStructure &&
                $externalSourceImpl instanceof CompetitionDetails) {
                $sport = $this->getSportFromInput($input);
                $league = $this->getLeagueFromInput($input);
                $season = $this->getSeasonFromInput($input);
                switch ($entity) {
                    case Entity::GAMES:
                        $this->importer->importSchedule(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season,
                            $this->getGameRoundNrRangeFromInput($input)
                        );
                        return 0;
                    case Entity::GAMEDETAILS:
                        $this->importAgainstGameDetails(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season,
                            (string)$this->getIdFromInput($input)
                        );
                        return 0;
                    case Entity::IMAGES:
                        $this->importImages(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $league,
                            $season
                        );
                        return 0;
                }
            }
            throw new \Exception('objectType "' . $entity . '" kan niet worden opgehaald uit externe bronnen', E_ERROR);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return 0;
    }

//    protected function importSports(Implementation $externalSourceImpl): void
//    {
//        $this->importer->importSports($externalSourceImpl);
//    }
//
//    protected function importAssociations(Implementation $externalSourceImpl, Sport $sport): void
//    {
//        $this->importer->importAssociations($externalSourceImpl, $sport);
//    }
//
//    protected function importSeasons(Implementation $externalSourceImpl): void
//    {
//        $this->importer->importSeasons($externalSourceImpl);
//    }
//
//    protected function importLeagues(Implementation $externalSourceImpl, Association $association): void
//    {
//        $this->importer->importLeagues($externalSourceImpl, $association);
//    }
//
//    protected function importCompetition(Implementation $externalSourceImpl,
//        Sport $sport, Association $association, League $league, Season $season): void
//    {
//        $this->importer->importCompetition($externalSourceImpl, $sport, $association, $league, $season );
//    }

    protected function importAgainstGameDetails(
        Competitions $externalSourceCompetitions,
        CompetitionStructure $externalSourceCompetitionStructure,
        CompetitionDetails $externalSourceCompetitionDetails,
        ExternalSource $externalSource,
        Sport $sport,
        League $league,
        Season $season,
        string $externalGameId
    ): void
    {
        // bepaal de period waarin gezocht moet worden
        // voor de cronjob is 24, 3 en 2 uur na de start van de wedstrijd


//        $period = new Period(
//            new \DateTimeImmutable('2020-10-18 12:29'),
//            new \DateTimeImmutable('2020-10-18 12:31') ); // klaiber
//        // HIER VERDER
//        /*$period = new Period(
//            new \DateTimeImmutable('2020-09-01 08:00'),
//            new \DateTimeImmutable('2020-09-21 08:00') );
//        $period = new Period(
//            new \DateTimeImmutable('2020-09-21 08:00'),
//            new \DateTimeImmutable('2020-10-16 08:00') );
//        $period = new Period(
//            new \DateTimeImmutable('2020-10-16 08:00'),
//            new \DateTimeImmutable('2020-10-19 08:00') );*/
//        $period = new Period(
//            new \DateTimeImmutable('2020-10-19 08:00'),
//            new \DateTimeImmutable('2020-12-11 08:00')
//        );

        //$games = $this->againstGameRepos->getCompetitionGames($competition, null, null, $period);

        //            foreach ($games as $game) {
//                $externalGameId = $this->againstGameAttacherRepos->findExternalId($externalSource, $game );
//                if( $externalGameId === null ) {
//                    $this->logger->error('no attacher find for gameId "' . (string)$game->getId() . '" is not finished');
//                }

       // $externalGameId

        $this->importer->importAgainstGameDetails(
            $externalSourceCompetitions,
            $externalSourceCompetitionStructure,
            $externalSourceCompetitionDetails,
            $externalSource,
            $sport,
            $league,
            $season,
            $externalGameId
        );
    }

    protected function importImages(
        CompetitionStructure $externalSourceCompetitionStructure,
        CompetitionDetails $externalSourceCompetitionDetails,
        ExternalSource $externalSource,
        League $league,
        Season $season
    ): void
    {
        $localPath = $this->config->getString('www.apiurl-localpath');
        $localPath .= $this->config->getString('images.personsSuffix');
        $publicPath = $this->config->getString('www.apiurl');
        $publicPath .= $this->config->getString('images.personsSuffix');
        $maxWidth = 150;
        $this->importer->importPersonImages(
            $externalSourceCompetitionDetails,
            $externalSource,
            $league,
            $season,
            $localPath,
            $publicPath,
            $maxWidth
        );

        $localPath = $this->config->getString('www.apiurl-localpath');
        $localPath .= $this->config->getString('images.teamsSuffix');
        $publicPath = $this->config->getString('www.apiurl');
        $publicPath .= $this->config->getString('images.teamsSuffix');
        $maxWidth = 150;
        $this->importer->importTeamImages(
            $externalSourceCompetitionStructure,
            $externalSource,
            $league,
            $season,
            $localPath,
            $publicPath,
            $maxWidth
        );
    }
}
