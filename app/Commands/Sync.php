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
use Psr\Container\ContainerInterface;
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
use SuperElf\Totals\Syncer as TotalsSyncer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends Command
{
    protected AgainstGameRepository $againstGameRepos;
    protected PersonRepository $personRepos;
    protected S11PlayerRepository $s11PlayerRepos;
    protected GameRoundSyncer $gameRoundSyncer;
    protected S11PlayerSyncer $s11PlayerSyncer;
    protected StatisticsSyncer $statisticsSyncer;
    protected AppearanceSyncer $appearanceSyncer;
    protected TotalsSyncer $totalsSyncer;
    protected PoolGameSyncer $poolGameSyncer;
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

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:sync')
            // the short description shown while running "php bin/console list"
            ->setDescription('sync all superelf-data')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('syncs superelf-data after game-import');

        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'game-id');

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
        $this->addOption('gameRoundRange', null, InputOption::VALUE_OPTIONAL, '1-4');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-sync');
        $this->getLogger()->info('starting command app:sync');

        try {
            if ($this->executeManual($input)) {
                return 0;
            }
            $queueService = new QueueService($this->config->getArray('queue'));
            $timeoutInSeconds = 1;

            $queueService->receive($this->getReceiver(), $timeoutInSeconds, QueueService::GENERAL_QUEUE);
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
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

        $gameRoundNrRange = $this->inputHelper->getGameRoundNrRangeFromInput($input);
        if ($gameRoundNrRange !== null) {
            $this->syncGameRounds($competitionConfig, $gameRoundNrRange);
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
            $this->s11PlayerSyncer->sync($competitionConfig, $game);
            $this->statisticsSyncer->sync($competitionConfig, $game);
            $this->appearanceSyncer->sync($competitionConfig, $game);
            $this->totalsSyncer->sync($competitionConfig, $game);
            $this->poolGameSyncer->sync($competitionConfig, $game->getGameRoundNumber());
        } else {
            $this->getLogger()->info('game with gameId ' . (string)$gameId . ' not found');
        }
        return true;
    }

    protected function initLogger(InputInterface $input, string $name, MailHandler|null $mailHandler = null): void
    {
        parent::initLogger($input, $name);
        $this->gameRoundSyncer->setLogger($this->getLogger());
        $this->s11PlayerSyncer->setLogger($this->getLogger());
        $this->statisticsSyncer->setLogger($this->getLogger());
        $this->appearanceSyncer->setLogger($this->getLogger());
        $this->totalsSyncer->setLogger($this->getLogger());
        $this->poolGameSyncer->setLogger($this->getLogger());
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
            $this->gameRoundSyncer->sync($competitionConfig, $dates);
            $this->s11PlayerSyncer->sync($competitionConfig, $game);
            $this->statisticsSyncer->sync($competitionConfig, $game);
            $this->appearanceSyncer->sync($competitionConfig, $game);
            $this->totalsSyncer->sync($competitionConfig, $game);
            $this->poolGameSyncer->sync($competitionConfig, $game->getGameRoundNumber());
        } else { //  if ($event === GameEvent::UpdateScoresLineupsAndEvents) {
            $this->s11PlayerSyncer->sync($competitionConfig, $game);
            $this->statisticsSyncer->sync($competitionConfig, $game);
            $this->appearanceSyncer->sync($competitionConfig, $game);
            $this->totalsSyncer->sync($competitionConfig, $game);
            $this->poolGameSyncer->sync($competitionConfig, $game->getGameRoundNumber());
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
        $this->gameRoundSyncer->sync($competitionConfig, [$game->getStartDateTime(), $oldStartDateTime]);
        $this->s11PlayerSyncer->sync($competitionConfig, $game);
        $this->statisticsSyncer->sync($competitionConfig, $game);
        $this->totalsSyncer->sync($competitionConfig, $game);
        $this->appearanceSyncer->sync($competitionConfig, $game);
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

    protected function syncGameRounds(CompetitionConfig $competitionConfig, SportRange $gameRoundNrRange): void
    {
        $games = $this->getGames($competitionConfig->getSourceCompetition(), $gameRoundNrRange);
        foreach ($games as $game) {
            $this->gameRoundSyncer->sync($competitionConfig, [$game->getStartDateTime()]);
            $this->s11PlayerSyncer->sync($competitionConfig, $game);
            $this->statisticsSyncer->sync($competitionConfig, $game);
            $this->appearanceSyncer->sync($competitionConfig, $game);
            $this->totalsSyncer->sync($competitionConfig, $game);
        }
        for ($nr = $gameRoundNrRange->getMin(); $nr <= $gameRoundNrRange->getMax(); $nr++) {
            $this->poolGameSyncer->sync($competitionConfig, $nr);
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
