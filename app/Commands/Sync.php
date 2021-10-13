<?php
declare(strict_types=1);

namespace App\Commands\Sync;

use App\Mailer;
use App\QueueService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use App\Command;
use Interop\Queue\Consumer;
use Interop\Queue\Message;
use League\Period\Period;
use Psr\Container\ContainerInterface;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\State;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SuperElf\GameRound;
use Sports\Game\Against\Repository as AgainstGameRepository;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;

class GameRounds extends Command
{
    protected AgainstGameRepository $againstGameRepos;
    protected GameRoundRepository $gameRoundRepos;
    protected ViewPeriodRepository $viewPeriodRepos;
    protected EntityManager $entityManager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, 'command-calculate-gamerounds');
        /** @var AgainstGameRepository againstGameRepos */
        $this->againstGameRepos = $container->get(AgainstGameRepository::class);
        /** @var GameRoundRepository gameRoundRepos */
        $this->gameRoundRepos = $container->get(GameRoundRepository::class);
        /** @var ViewPeriodRepository viewPeriodRepos */
        $this->viewPeriodRepos = $container->get(ViewPeriodRepository::class);
        /** @var EntityManager entityManager */
        $this->entityManager = $container->get(EntityManager::class);
    }

    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:calculate-gamerounds')
            // the short description shown while running "php bin/console list"
            ->setDescription('Calculates in the gamerounds per viewperiod')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Calculates the gamerounds after game-import');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLoggerFromInput($input);
        $this->logger->info('starting command app:calculate-gamerounds');

        try {
            $queueService = new QueueService($this->config->getArray('queue'));
            $timeoutInSeconds = 295;

            $queueName = QueueService::NAME_UPDATE_GAME_QUEUE;
            $queueService->receive($this->getReceiver($queueService), $timeoutInSeconds, $queueName);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return 0;
    }

    protected function getReceiver(QueueService $queueService): callable
    {
        return function (Message $message, Consumer $consumer) use ($queueService) : void {
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
                    $this->process($queueService, $game, $oldStartDateTime);
                } else {
                    $this->logger->info('game with gameId ' . (string)$content->gameId . ' not found');
                }
                $consumer->acknowledge($message);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $consumer->reject($message);
            }
        };
    }
}
