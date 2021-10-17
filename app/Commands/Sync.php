<?php
declare(strict_types=1);

namespace App\Commands;

use App\QueueService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use App\Command;
use Interop\Queue\Consumer;
use Interop\Queue\Message;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sports\Game\Against\Repository as AgainstGameRepository;
use SuperElf\GameRound\Syncer as GameRoundSyncer;
use SuperElf\Player\Syncer as S11PlayerSyncer;
use SuperElf\Statistics\Syncer as StatisticsSyncer;
use SuperElf\Substitute\Appearance\Syncer as AppearanceSyncer;

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
        parent::__construct($container, 'command-sync');
        /** @var AgainstGameRepository againstGameRepos */
        $this->againstGameRepos = $container->get(AgainstGameRepository::class);
        /** @var GameRoundSyncer gameRoundSyncer */
        $this->gameRoundSyncer = $container->get(GameRoundSyncer::class);
        /** @var S11PlayerSyncer s11PlayerSyncer */
        $this->s11PlayerSyncer = $container->get(S11PlayerSyncer::class);
        /** @var StatisticsSyncer statisticsSyncer */
        $this->statisticsSyncer = $container->get(StatisticsSyncer::class);
        /** @var AppearanceSyncer appearanceSyncer */
        $this->appearanceSyncer = $container->get(AppearanceSyncer::class);
        /** @var EntityManager entityManager */
        $this->entityManager = $container->get(EntityManager::class);


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
        $this->initLoggerFromInput($input);
        $this->logger->info('starting command app:sync');

        try {
            $queueService = new QueueService($this->config->getArray('queue'));
            $timeoutInSeconds = 1;

            $queueName = QueueService::NAME_UPDATE_GAME_QUEUE;
            $queueService->receive($this->getReceiver(), $timeoutInSeconds, $queueName);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return 0;
    }

    protected function getReceiver(): callable
    {
        return function (Message $message, Consumer $consumer) : void {
            // process message
            $this->logger->info('------ EXECUTING ------');
            try {
                /** @var stdClass $content */
                $content = json_decode($message->getBody());
                $game = null;
                if (property_exists($content, "gameId")) {
                    $game = $this->againstGameRepos->find((int)$content->gameId);
                }
                $oldStartDateTime = null;
                if (property_exists($content, "oldTimestamp")) {
                    $oldStartDateTime = new \DateTimeImmutable("@" . (string)$content->oldTimestamp);
                }
                if ($game !== null) {
                    $this->gameRoundSyncer->sync($game, $oldStartDateTime);
                    $this->s11PlayerSyncer->sync($game);
                    $this->statisticsSyncer->sync($game);
                    $this->appearanceSyncer->sync($game);
                } else {
                    $this->logger->info('game with gameId ' . (string)$content->gameId . ' not found');
                }
                $consumer->acknowledge($message);
                die();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $consumer->reject($message);
            }
        };
    }
}
