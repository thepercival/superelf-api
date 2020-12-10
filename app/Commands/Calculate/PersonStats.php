<?php

declare(strict_types=1);

namespace App\Commands\Calculate;

use App\Mailer;
use App\QueueService;
use Doctrine\ORM\EntityManager;
use App\Command;
use Interop\Queue\Consumer;
use Interop\Queue\Message;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sports\Game\Repository as GameRepository;
use SuperElf\Calculator\ViewPeriodPerson as ViewPeriodPersonGameRoundsCalculator;

class PersonStats extends Command
{
    protected GameRepository $gameRepos;
    protected ViewPeriodPersonGameRoundsCalculator $calculator;
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->mailer = $container->get(Mailer::class);
        $this->gameRepos = $container->get(GameRepository::class);
        $this->calculator = $container->get(ViewPeriodPersonGameRoundsCalculator::class);
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:calculate-personstats')
            // the short description shown while running "php bin/console list"
            ->setDescription('Calculates the person-stats after game-import')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Calculates the person-stats after game-import');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLogger($input, 'command-personstats-calculate');
        $this->logger->info('starting command app:calculate-personstats');

        try {
            $queueService = new QueueService($this->config->getArray('queue'));
            $timeoutInSeconds = 295;
            $queueName = QueueService::NAME_UPDATE_GAMEDETAILS_QUEUE;
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
                $content = json_decode($message->getBody());
                $game = null;
                if (property_exists($content, "gameId")) {
                    $game = $this->gameRepos->find((int)$content->gameId);
                }
                if ($game !== null) {
                    $this->calculator->calculate($game);
                } else {
                    $this->logger->info('game with gameId ' . $content->gameId . ' not found');
                }
                $consumer->acknowledge($message);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $consumer->reject($message);
            }
        };
    }
}
