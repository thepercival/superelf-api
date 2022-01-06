<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\MailHandler;
use App\QueueService;
use Doctrine\ORM\EntityManager;
use Exception;
use Interop\Queue\Consumer;
use Interop\Queue\Message;
use Psr\Container\ContainerInterface;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\State;
use SportsHelpers\SportRange;
use stdClass;
use SuperElf\GameRound\Syncer as GameRoundSyncer;
use SuperElf\Player\Syncer as S11PlayerSyncer;
use SuperElf\Statistics\Syncer as StatisticsSyncer;
use SuperElf\Substitute\Appearance\Syncer as AppearanceSyncer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends Command
{
    protected AgainstGameRepository $againstGameRepos;
    protected GameRoundSyncer $gameRoundSyncer;
    protected S11PlayerSyncer $s11PlayerSyncer;
    protected StatisticsSyncer $statisticsSyncer;
    protected AppearanceSyncer $appearanceSyncer;
    protected EntityManager $entityManager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        /** @var GameRoundSyncer $gameRoundSyncer */
        $gameRoundSyncer = $container->get(GameRoundSyncer::class);
        $this->gameRoundSyncer = $gameRoundSyncer;

        /** @var S11PlayerSyncer $s11PlayerSyncer */
        $s11PlayerSyncer = $container->get(S11PlayerSyncer::class);
        $this->s11PlayerSyncer = $s11PlayerSyncer;

        /** @var StatisticsSyncer $statisticsSyncer */
        $statisticsSyncer = $container->get(StatisticsSyncer::class);
        $this->statisticsSyncer = $statisticsSyncer;

        /** @var AppearanceSyncer $appearanceSyncer */
        $appearanceSyncer = $container->get(AppearanceSyncer::class);
        $this->appearanceSyncer = $appearanceSyncer;

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
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

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-sync');
        $this->getLogger()->info('starting command app:sync');

        try {
            $queueService = new QueueService($this->config->getArray('queue'));
            $timeoutInSeconds = 1;

            $competition = $this->getCompetitionFromInput($input);
            if ($competition !== null) {
                $gameRoundNrRange = $this->getGameRoundNrRangeFromInput($input);
                if ($gameRoundNrRange !== null) {
                    $this->syncGameRounds($competition, $gameRoundNrRange);
                    return 0;
                }
            }


            $gameId = $this->getIdFromInput($input, 0);
            if ($gameId !== 0) {
                $this->syncGame($gameId, null);
                return 0;
            }

            $queueName = QueueService::NAME_UPDATE_GAME_QUEUE;
            $queueService->receive($this->getReceiver(), $timeoutInSeconds, $queueName);
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    protected function initLogger(InputInterface $input, string $name, MailHandler|null $mailHandler = null): void
    {
        parent::initLogger($input, $name);
        $this->gameRoundSyncer->setLogger($this->getLogger());
        $this->s11PlayerSyncer->setLogger($this->getLogger());
        $this->statisticsSyncer->setLogger($this->getLogger());
        $this->appearanceSyncer->setLogger($this->getLogger());
    }

    protected function getReceiver(): callable
    {
        return function (Message $message, Consumer $consumer): void {
            // process message
            $this->getLogger()->info('------ EXECUTING ------');
            try {
                /** @var stdClass $content */
                $content = json_decode($message->getBody());
                if (!property_exists($content, "gameId")) {
                    throw new \Exception('no gameId found in queue-message', E_ERROR);
                }
                $gameId = (int)$content->gameId;
                $oldStartDateTime = null;
                if (property_exists($content, "oldTimestamp")) {
                    $oldStartDateTime = new \DateTimeImmutable("@" . (string)$content->oldTimestamp);
                }
                $this->syncGame($gameId, $oldStartDateTime);
                $consumer->acknowledge($message);
                die();
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
                $consumer->reject($message);
            }
        };
    }

    protected function syncGame(string|int $gameId, \DateTimeImmutable|null $oldStartDateTime): void
    {
        $game = $this->againstGameRepos->find((int)$gameId);
        if ($game === null) {
            $this->getLogger()->info('game with gameId ' . (string)$gameId . ' not found');
            return;
        }
        $this->gameRoundSyncer->sync($game, $oldStartDateTime);
        $this->s11PlayerSyncer->sync($game);
        $this->statisticsSyncer->sync($game);
        $this->appearanceSyncer->sync($game);
    }

    /**
     * @param Competition $competition
     * @param SportRange $gameRoundNrRange
     * @throws Exception
     */
    protected function syncGameRounds(Competition $competition, SportRange $gameRoundNrRange): void
    {
        $games = $this->getGames($competition, $gameRoundNrRange);
        foreach ($games as $game) {
            $this->gameRoundSyncer->sync($game, null);
            $this->s11PlayerSyncer->sync($game);
            $this->statisticsSyncer->sync($game);
            $this->appearanceSyncer->sync($game);
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
                State::Created + State::InProgress + State::Finished,
                $gameRoundNumber,
                $competition->getSeason()->getPeriod()
            );
            $games = array_merge($games, $gameRoundGames);
        }
        return $games;
    }
}
