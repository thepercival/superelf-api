<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\MailHandler;
use App\QueueService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Interop\Queue\Consumer;
use Interop\Queue\Message;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sports\Competition;
use Sports\Game;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\State as GameState;
use Sports\Person\Repository as PersonRepository;
use SportsHelpers\SportRange;
use SportsImport\Event\Action\Game as GameEventAction;
use SportsImport\Event\Action\Person as PersonEventAction;
use SportsImport\Event\Game as GameEvent;
use SportsImport\Event\Person as PersonEvent;
use stdClass;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Game\Syncer as PoolGameSyncer;
use SuperElf\GameRound\Syncer as GameRoundSyncer;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player\Syncer as S11PlayerSyncer;
use SuperElf\Statistics\Syncer as StatisticsSyncer;
use SuperElf\Substitute\Appearance\Syncer as AppearanceSyncer;
use SuperElf\Totals\TotalsSyncer as TotalsSyncer;
use SuperElf\Achievement\Syncer as AchievementsSyncer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:sync --league=Eredivisie --season=2022/2023 --id=181
 */
final class Sync extends Command
{
    private string $customName = 'sync';

    protected AgainstGameRepository $againstGameRepos;
    protected PersonRepository $personRepos;
    protected S11PlayerRepository $s11PlayerRepos;
    protected GameRoundSyncer $gameRoundSyncer;
    protected S11PlayerSyncer $s11PlayerSyncer;
    protected StatisticsSyncer $statisticsSyncer;
    protected AppearanceSyncer $appearanceSyncer;
    protected TotalsSyncer $totalsSyncer;
    protected PoolGameSyncer $poolGameSyncer;
    protected AchievementsSyncer $achievementsSyncer;
    protected CompetitionConfigRepository $competitionConfigRepos;
    protected EntityManagerInterface $entityManager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        /** @var PersonRepository $personRepos */
        $personRepos = $container->get(PersonRepository::class);
        $this->personRepos = $personRepos;

        /** @var S11PlayerRepository $s11PlayerRepos */
        $s11PlayerRepos = $container->get(S11PlayerRepository::class);
        $this->s11PlayerRepos = $s11PlayerRepos;

        /** @var GameRoundSyncer $gameRoundSyncer */
        $gameRoundSyncer = $container->get(GameRoundSyncer::class);
        $this->gameRoundSyncer = $gameRoundSyncer;

        /** @var PoolGameSyncer $poolGameSyncer */
        $poolGameSyncer = $container->get(PoolGameSyncer::class);
        $this->poolGameSyncer = $poolGameSyncer;

        /** @var S11PlayerSyncer $s11PlayerSyncer */
        $s11PlayerSyncer = $container->get(S11PlayerSyncer::class);
        $this->s11PlayerSyncer = $s11PlayerSyncer;

        /** @var StatisticsSyncer $statisticsSyncer */
        $statisticsSyncer = $container->get(StatisticsSyncer::class);
        $this->statisticsSyncer = $statisticsSyncer;

        /** @var AppearanceSyncer $appearanceSyncer */
        $appearanceSyncer = $container->get(AppearanceSyncer::class);
        $this->appearanceSyncer = $appearanceSyncer;

        /** @var TotalsSyncer $totalsSyncer */
        $totalsSyncer = $container->get(TotalsSyncer::class);
        $this->totalsSyncer = $totalsSyncer;

        /** @var AchievementsSyncer $achievementsSyncer */
        $achievementsSyncer = $container->get(AchievementsSyncer::class);
        $this->achievementsSyncer = $achievementsSyncer;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:' . $this->customName)
            // the short description shown while running "php bin/console list"
            ->setDescription('sync all superelf-data')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('syncs superelf-data after game-import');

        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'game-id');

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
        $this->addOption('gameRoundRange', null, InputOption::VALUE_OPTIONAL, '1-4');
        $this->addOption('with-achievements', null, InputOption::VALUE_NONE);

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->customInitLogger($input);
        $logger->info('starting command app:sync');

        try {
            if ($this->executeManual($input)) {
                return 0;
            }
            $queueService = new QueueService($this->config->getArray('queue'));
            $timeoutInSeconds = 1;

            $queueService->receive($this->getReceiver(), $timeoutInSeconds, QueueService::GENERAL_QUEUE);
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return 0;
    }

    protected function executeManual(InputInterface $input): bool
    {
        try {
            $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);
        } catch (\Exception $e) {
            return false;
        }

        /** @var bool|null $withAchievementsTmp */
        $withAchievementsTmp = $input->getOption('with-achievements');
        $withAchievements = is_bool($withAchievementsTmp) ? $withAchievementsTmp : false;

        $gameRoundNrRange = $this->inputHelper->getGameRoundNrRangeFromInput($input);
        if ($gameRoundNrRange !== null) {
            $this->syncGameRounds($competitionConfig, $gameRoundNrRange, $withAchievements);
            return true;
        }

        $gameIdTmp = $this->inputHelper->getIdFromInput($input, 0);
        $gameId = (int)$gameIdTmp;
        if ($gameId === 0) {
            return false;
        }

        $game = $this->againstGameRepos->find($gameId);
        if ($game !== null) {
            $competitionConfig = $this->getCompetitionConfig($game);
            $this->s11PlayerSyncer->syncS11Players($competitionConfig, $game);
            $this->statisticsSyncer->syncStatistics($competitionConfig, $game);
            $this->appearanceSyncer->syncSubstituteAppearances($competitionConfig, $game);
            $this->totalsSyncer->syncTotals($competitionConfig, $game);
            // $this->poolGameSyncer->syncPoolCompetitions($competitionConfig, $game->getGameRoundNumber());
            if( $withAchievements ) {
                $this->achievementsSyncer->syncPoolAchievements($competitionConfig);
            }
        } else {
            $this->getLogger()->info('game with gameId ' . (string)$gameId . ' not found');
        }
        return true;
    }

    protected function customInitLogger(InputInterface $input): LoggerInterface
    {
        $loggerName = 'command-' . $this->customName;
        $logger = $this->initLoggerNew(
            $this->getLogLevelFromInput($input),
            $this->getStreamDefFromInput($input, $loggerName),
            $loggerName,
        );
        $this->gameRoundSyncer->setLogger($logger);
        $this->s11PlayerSyncer->setLogger($logger);
        $this->statisticsSyncer->setLogger($logger);
        $this->appearanceSyncer->setLogger($logger);
        $this->totalsSyncer->setLogger($logger);
        $this->poolGameSyncer->setLogger($logger);
        $this->achievementsSyncer->setLogger($logger);
        return $logger;
    }

    protected function getReceiver(): callable
    {
        return function (Message $message, Consumer $consumer): void {
            // process message
            $this->getLogger()->info('------ EXECUTING ------');
            try {
                /** @var stdClass $content */
                $content = json_decode($message->getBody());
                if (!property_exists($content, "action")) {
                    throw new \Exception('no action found in queue-message', E_ERROR);
                }

                $updatedObjectFromQueueMessage = $this->getEventFromQueueMessage($content);
                if ($updatedObjectFromQueueMessage instanceof PersonEvent) {
                    $this->syncPerson($updatedObjectFromQueueMessage);
                } elseif ($updatedObjectFromQueueMessage instanceof GameEvent) {
                    $this->syncGame($updatedObjectFromQueueMessage);
                }

                $consumer->acknowledge($message);
                die();
            } catch (\Exception $e) {
                $consumer->reject($message, true);
                $this->getLogger()->error($e->getMessage());
            }
        };
    }

    protected function getEventFromQueueMessage(stdClass $content): GameEvent|PersonEvent|null
    {
        if (!property_exists($content, "action")) {
            $this->getLogger()->error('no action found in message');
            return null;
        }

        $action = PersonEventAction::tryFrom((string)$content->action);
        if ($action === PersonEventAction::Create) {
            if (!property_exists($content, "personId")) {
                $this->getLogger()->error('no personId found in message');
                return null;
            }
            $person = $this->personRepos->find((int)$content->personId);
            if ($person === null) {
                $this->getLogger()->info('person with personId ' . (string)$content->personId . ' not found');
                return null;
            }
            if (!property_exists($content, "seasonId")) {
                $this->getLogger()->error('no seasonId found in message');
                return null;
            }
            $season = $this->seasonRepos->find((int)$content->seasonId);
            if ($season === null) {
                $this->getLogger()->info('season with seasonId ' . (string)$content->seasonId . ' not found');
                return null;
            }
            return new PersonEvent($action, $person, $season);
        }
        $action = GameEventAction::tryFrom((string)$content->action);
        if ($action === null) {
            return null;
        }
        if (!property_exists($content, "gameId")) {
            $this->getLogger()->error('no gameId found in message');
            return null;
        }
        $game = $this->againstGameRepos->find((int)$content->gameId);
        if ($game === null) {
            $this->getLogger()->info('game with gameId ' . (string)$content->gameId . ' not found');
            return null;
        }
        $oldDateTime = null;
        if (property_exists($content, "oldTimestamp")) {
            $oldDateTime = new \DateTimeImmutable("@" . (string)$content->oldTimestamp);
        }
        return new GameEvent($action, $game, $oldDateTime);
    }

    protected function syncPerson(PersonEvent $event): void
    {
        $competitionConfigs = $this->competitionConfigRepos->findBySeason($event->getSeason());

        foreach ($competitionConfigs as $competitionConfig) {
            foreach ($competitionConfig->getViewPeriods() as $viewPeriod) {
//                if( !$viewPeriod->contains($event->getDateTime())) {
//                    continue;
//                }
                $s11Player = $this->s11PlayerSyncer->syncS11Player($viewPeriod, $event->getPerson());
                $this->s11PlayerRepos->save($s11Player);
                // $this->statisticsSyncer->sync($competitionConfig, $game);
            }
        }
    }

    protected function syncGame(GameEvent $event): void
    {
        $game = $event->getGame();
        $oldDateTime = $event->getOldDateTime();
        $competitionConfig = $this->getCompetitionConfig($game);

        if ($event->getAction() === GameEventAction::Create
            || $event->getAction() === GameEventAction::UpdateBasics
            || $event->getAction() === GameEventAction::Reschedule) {
            $dates = [$game->getStartDateTime()];

            if ($oldDateTime !== null) {
                $dates[] = $oldDateTime;
            }
            $this->gameRoundSyncer->syncViewPeriodGameRounds($competitionConfig, $dates);
            $this->s11PlayerSyncer->syncS11Players($competitionConfig, $game);
            $this->statisticsSyncer->syncStatistics($competitionConfig, $game);
            $this->appearanceSyncer->syncSubstituteAppearances($competitionConfig, $game);
            $this->totalsSyncer->syncTotals($competitionConfig, $game);
            $this->poolGameSyncer->syncPoolCompetitions($competitionConfig, $game->getGameRoundNumber());

        } else { //  if ($event === GameEvent::UpdateScoresLineupsAndEvents) {
            $this->s11PlayerSyncer->syncS11Players($competitionConfig, $game);
            $this->statisticsSyncer->syncStatistics($competitionConfig, $game);
            $this->appearanceSyncer->syncSubstituteAppearances($competitionConfig, $game);
            $this->totalsSyncer->syncTotals($competitionConfig, $game);
            $this->poolGameSyncer->syncPoolCompetitions($competitionConfig, $game->getGameRoundNumber());
        }
    }

    protected function syncRescheduleGame(
        CompetitionConfig $competitionConfig,
        string|int $gameId,
        \DateTimeImmutable $oldStartDateTime
    ): void {
        $game = $this->againstGameRepos->find((int)$gameId);
        if ($game === null) {
            $this->getLogger()->info('game with gameId ' . (string)$gameId . ' not found');
            return;
        }
        $this->gameRoundSyncer->syncViewPeriodGameRounds($competitionConfig, [$game->getStartDateTime(), $oldStartDateTime]);
        $this->s11PlayerSyncer->syncS11Players($competitionConfig, $game);
        $this->statisticsSyncer->syncStatistics($competitionConfig, $game);
        $this->totalsSyncer->syncTotals($competitionConfig, $game);
        $this->appearanceSyncer->syncSubstituteAppearances($competitionConfig, $game);
    }

    protected function getGameFromQueueMessage(stdClass $queueMessage): AgainstGame
    {
        if (!property_exists($queueMessage, "gameId")) {
            throw new \Exception('no gameId found in queue-message', E_ERROR);
        }
        $gameId = (int)$queueMessage->gameId;
        $game = $this->againstGameRepos->find($gameId);
        if ($game === null) {
            throw new \Exception('game with gameId ' . (string)$gameId . ' not found', E_ERROR);
        }
        return $game;
    }

    protected function syncGameRounds(CompetitionConfig $competitionConfig, SportRange $gameRoundNrRange, bool $withAchievements): void
    {
        $games = $this->getGames($competitionConfig->getSourceCompetition(), $gameRoundNrRange);
        foreach ($games as $game) {
            $this->gameRoundSyncer->syncViewPeriodGameRounds($competitionConfig, [$game->getStartDateTime()]);
            $this->s11PlayerSyncer->syncS11Players($competitionConfig, $game);
            $this->statisticsSyncer->syncStatistics($competitionConfig, $game);
            $this->appearanceSyncer->syncSubstituteAppearances($competitionConfig, $game);
            $this->totalsSyncer->syncTotals($competitionConfig, $game);
        }
        for ($nr = $gameRoundNrRange->getMin(); $nr <= $gameRoundNrRange->getMax(); $nr++) {
            $this->poolGameSyncer->syncPoolCompetitions($competitionConfig, $nr);
        }
        if ( $withAchievements ) {
            $this->achievementsSyncer->syncPoolAchievements($competitionConfig);
        }
    }

    /**
     * @param Competition $competition
     * @param SportRange $gameRoundNrRange
     * @return list<AgainstGame>
     * @throws Exception
     */
    protected function getGames(Competition $competition, SportRange $gameRoundNrRange): array
    {
        $games = [];
        foreach ($gameRoundNrRange->toArray() as $gameRoundNumber) {
            $gameRoundGames = $this->againstGameRepos->getCompetitionGames(
                $competition,
                [GameState::Created, GameState::InProgress, GameState::Finished],
                $gameRoundNumber,
                $competition->getSeason()->getPeriod()
            );
            $games = array_merge($games, $gameRoundGames);
        }
        return $games;
    }

    protected function getCompetitionConfig(Game $game): CompetitionConfig
    {
        $competition = $game->getRound()->getNumber()->getCompetition();

        $competitionConfig = $this->competitionConfigRepos->findOneBy(['sourceCompetition' => $competition]);
        if ($competitionConfig === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        return $competitionConfig;
    }
}
