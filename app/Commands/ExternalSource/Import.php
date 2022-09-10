<?php

declare(strict_types=1);

namespace App\Commands\ExternalSource;

use App\Commands\ExternalSource as ExternalSourceCommand;
use App\QueueService;
use DateTimeImmutable;
use League\Period\Period;
use Psr\Container\ContainerInterface;
use Sports\Competition;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\League;
use Sports\Season;
use Sports\Sport;
use SportsImport\Entity;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\Competitions;
use SportsImport\ExternalSource\CompetitionStructure;
use SportsImport\ExternalSource\GamesAndPlayers;
use SportsImport\ExternalSource\Transfers;
use SportsImport\Importer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:import
 *      sofascore game          --league=Eredivisie
 *                              --season=2022/2023
 *                              --sport=football
 *                              --startDateTime="2022-09-02 20:00"
 *                              --loglevel=200
 */
class Import extends ExternalSourceCommand
{
    protected Importer $importer;
    protected AgainstGameRepository $againstGameRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var Importer $importer */
        $importer = $container->get(Importer::class);
        $this->importer = $importer;

        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

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
        $this->addOption('startDateTime', null, InputOption::VALUE_OPTIONAL, 'Y-m-d H:i');
        $this->addOption('no-events', null, InputOption::VALUE_NONE, 'no-events');
        $this->addOption('teamId', null, InputOption::VALUE_OPTIONAL, '12');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->initLogger($input, 'command-import');
            $this->importer->setLogger($this->getLogger());
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
                $this->importer->setGameEventSender(new QueueService($this->config->getArray('queue')));
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
                $externalSourceImpl instanceof Transfers) {
                $sport = $this->inputHelper->getSportFromInput($input);
                $league = $this->inputHelper->getLeagueFromInput($input);
                $season = $this->inputHelper->getSeasonFromInput($input);
                switch ($entity) {
                    case Entity::TRANSFERS:
                        if ($noEvents !== true) {
                            $this->importer->setPersonEventSender(new QueueService($this->config->getArray('queue')));
                        }
                        $team = $this->inputHelper->getTeamFromInput($input);
                        if ($team === null) {
                            throw new \Exception('team must be set', E_ERROR);
                        }
                        $this->importer->importTeamTransfers(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl->getExternalSource(),
                            $sport,
                            $league,
                            $season,
                            $team
                        );
                        return 0;
//                    case Entity::TEAMCOMPETITORS:
//                        $this->importer->importTeamCompetitors(
//                            $externalSourceImpl,
//                            $externalSourceImpl,
//                            $externalSourceImpl->getExternalSource(),
//                            $sport,
//                            $league,
//                            $season
//                        );
//                        return 0;
//                    case Entity::STRUCTURE:
//                        $this->importer->importStructure(
//                            $externalSourceImpl,
//                            $externalSourceImpl,
//                            $externalSourceImpl->getExternalSource(),
//                            $sport,
//                            $league,
//                            $season
//                        );
//                        return 0;
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
                            $input
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
        InputInterface $input
    ): void {
        $externalGameId = (string)$this->inputHelper->getIdFromInput($input, '0');
        $dontUseCache = $this->getGameCacheOptionFromInput($input);

        if ($externalGameId !== '0') {
            $externalGameIds = [$externalGameId];
        } else {
            $competition = $this->inputHelper->getCompetitionFromInput($input);
            if ($competition === null) {
                throw new \Exception('competition can not be null', E_ERROR);
            }
            $dateTime = $this->inputHelper->getDateTimeOptionalFromInput($input, 'startDateTime', 'Y-m-d H:i');
            $externalGameIds = $this->getExternalGameIdsByDateTime($competition, $externalSource, $dateTime);
        }
        foreach ($externalGameIds as $externalGameId) {
            $this->importer->importAgainstGameBasicsLineupsAndEvents(
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

    /**
     * @param Competition $competition
     * @param ExternalSource $externalSource
     * @param DateTimeImmutable|null $startDateTime
     * @return list<string>
     */
    protected function getExternalGameIdsByDateTime(
        Competition $competition,
        ExternalSource $externalSource,
        DateTimeImmutable|null $startDateTime
    ): array {
        $externalGameIds = [];

        // ---- START : SCHEDULE OPTIONS -----------
        if ($startDateTime === null) {
            $minutesAfterStart = [120, 180, 60 * 24];
            $currentTmp = new \DateTimeImmutable( /*'now', new \DateTimeZone('Europe/Amsterdam')*/);
            $currentDateTime = $currentTmp->setTime((int)$currentTmp->format("H"), (int)$currentTmp->format("i"));
        } else {
            $minutesAfterStart = [0];
            $currentDateTime = $startDateTime;
        }
        // ------ END : SCHEDULE OPTIONS ----------

        foreach ($minutesAfterStart as $nrOfMinutesAfterStart) {
            $startDateTime = $currentDateTime->sub(new \DateInterval('PT' . $nrOfMinutesAfterStart . 'M'));
            if ($startDateTime === false) {
                continue;
            }
            $periodStart = $startDateTime->sub(new \DateInterval('PT1S'));
            if ($periodStart === false) {
                continue;
            }
            $period = new Period($periodStart, $startDateTime->add(new \DateInterval('PT1S')));

            $games = $this->againstGameRepos->getCompetitionGames($competition, null, null, $period);
            $msg = 'for ' . $nrOfMinutesAfterStart . ' minutes after ' . $startDateTime->format(
                    \DateTimeInterface::ISO8601
                ) . ' there were ' . count($games) . ' games found';
            $this->getLogger()->info($msg);
            foreach ($games as $game) {
                $externalGameId = $this->againstGameAttacherRepos->findExternalId($externalSource, $game);
                if ($externalGameId === null) {
                    $this->getLogger()->error(
                        'no attacher find for gameId "' . (string)$game->getId() . '" is not finished'
                    );
                } else {
                    $externalGameIds[] = $externalGameId;
                }
            }
        }
        return $externalGameIds;
    }
}
