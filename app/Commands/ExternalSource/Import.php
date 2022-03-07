<?php

declare(strict_types=1);

namespace App\Commands\ExternalSource;

use App\Commands\ExternalSource as ExternalSourceCommand;
use App\QueueService;
use Psr\Container\ContainerInterface;
use Sports\League;
use Sports\Season;
use Sports\Sport;
use SportsImport\Entity;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\Competitions;
use SportsImport\ExternalSource\CompetitionStructure;
use SportsImport\ExternalSource\GamesAndPlayers;
use SportsImport\Importer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Import extends ExternalSourceCommand
{
    protected Importer $importer;

    public function __construct(ContainerInterface $container)
    {
        /** @var Importer $importer */
        $importer = $container->get(Importer::class);
        $this->importer = $importer;

        parent::__construct($container);
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

        $this->addOption('gameRoundRange', null, InputOption::VALUE_OPTIONAL, '1-4');
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'game-id');
        $this->addOption('no-events', null, InputOption::VALUE_NONE, 'no-events');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->initLogger($input, 'command-import');
            $externalSourceName = (string)$input->getArgument('externalSource');
            $externalSourceImpl = $this->externalSourceFactory->createByName($externalSourceName);
            if ($externalSourceImpl === null) {
                $message = "voor '" . $externalSourceName . "' kan er geen externe bron worden gevonden";
                $this->getLogger()->error($message);
                return -1;
            }

            /** @var bool|null $noEvents */
            $noEvents = $input->getOption('no-events');
            if ($noEvents !== true) {
                $this->importer->setEventSender(new QueueService($this->config->getArray('queue')));
            }

            $entity = $this->getEntityFromInput($input);

            if ($externalSourceImpl instanceof Competitions) {
                switch ($entity) {
                    case Entity::SPORTS:
                        $this->importer->importSports($externalSourceImpl, $externalSourceImpl->getExternalSource());
                        return 0;
                    case Entity::SEASONS:
                        $this->importer->importSeasons($externalSourceImpl, $externalSourceImpl->getExternalSource());
                        return 0;
                    case Entity::ASSOCIATIONS:
                        $sport = $this->inputHelper->getSportFromInput($input);
                        $this->importer->importAssociations(
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport
                        );
                        return 0;
                    case Entity::LEAGUES:
                        $sport = $this->inputHelper->getSportFromInput($input);
                        $association = $this->inputHelper->getAssociationFromInput($input);
                        $this->importer->importLeagues(
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $association
                        );
                        return 0;
                    case Entity::COMPETITIONS:
                        $sport = $this->inputHelper->getSportFromInput($input);
                        $league = $this->inputHelper->getLeagueFromInput($input);
                        $season = $this->inputHelper->getSeasonFromInput($input);
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
                $sport = $this->inputHelper->getSportFromInput($input);
                $league = $this->inputHelper->getLeagueFromInput($input);
                $season = $this->inputHelper->getSeasonFromInput($input);
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
                $externalSourceImpl instanceof GamesAndPlayers) {
                $sport = $this->inputHelper->getSportFromInput($input);
                $league = $this->inputHelper->getLeagueFromInput($input);
                $season = $this->inputHelper->getSeasonFromInput($input);
                switch ($entity) {
                    case Entity::GAMES_BASICS:
                        $this->importer->importGamesBasics(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season,
                            $this->getGameCacheOptionFromInput($input),
                            $this->inputHelper->getGameRoundNrRangeFromInput($input)
                        );
                        return 0;
                    case Entity::GAMES_COMPLEET:
                        $this->importer->importGamesComplete(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season,
                            $this->getGameCacheOptionFromInput($input),
                            $this->inputHelper->getGameRoundNrRangeFromInput($input)
                        );
                        return 0;
                    case Entity::GAME:
                        $this->importGame(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season,
                            (string)$this->inputHelper->getIdFromInput($input),
                            $this->getGameCacheOptionFromInput($input)
                        );
                        return 0;
                }
            }
            throw new \Exception('objectType "' . $entity . '" kan niet worden opgehaald uit externe bronnen', E_ERROR);
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    protected function importGame(
        Competitions $externalSourceCompetitions,
        CompetitionStructure $externalSourceCompetitionStructure,
        GamesAndPlayers $externalSourceGamesAndPlayers,
        ExternalSource $externalSource,
        Sport $sport,
        League $league,
        Season $season,
        string $externalGameId,
        bool $dontUseCache
    ): void {
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

        $this->importer->importAgainstGameLineupsAndEvents(
            $externalSourceCompetitions,
            $externalSourceCompetitionStructure,
            $externalSourceGamesAndPlayers,
            $externalSource,
            $sport,
            $league,
            $season,
            $externalGameId,
            $dontUseCache
        );
    }
}
