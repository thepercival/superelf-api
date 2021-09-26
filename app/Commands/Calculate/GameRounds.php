<?php

declare(strict_types=1);

namespace App\Commands\Calculate;

use App\Mailer;
use App\QueueService;
use Doctrine\ORM\EntityManager;
use Exception;
use App\Command;
use Interop\Queue\Consumer;
use Interop\Queue\Message;
use Psr\Container\ContainerInterface;
use Sports\Game;
use Sports\Game\Score\HomeAway as GameScoreHomeAway;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Sport;
use SuperElf\ScoreUnit;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SuperElf\Person;
use Sports\Game\Repository as GameRepository;
use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Competitor\Team as TeamCompetitor;
use SuperElf\GameRound;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\CompetitionPerson\GameRoundScore;
use SuperElf\CompetitionPerson\GameRoundScore\Repository as GameRoundScoreRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;

class GameRounds extends Command
{
    protected GameRepository $gameRepos;
    protected GameRoundRepository $gameRoundRepos;
    protected ViewPeriodRepository $viewPeriodRepos;
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->mailer = $container->get(Mailer::class);
        $this->gameRepos = $container->get(GameRepository::class);
        $this->gameRoundRepos = $container->get(GameRoundRepository::class);
        $this->viewPeriodRepos = $container->get(ViewPeriodRepository::class);
        $this->entityManager = $container->get(EntityManager::class);
    }

    protected function configure()
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
        $this->initLogger($input, 'command-gamerounds-calculate');
        $this->logger->info('starting command app:calculate-gamerounds');

        try {
            $queueService = new QueueService($this->config->getArray('queue'));
            $timeoutInSeconds = 295;

            $queueName = QueueService::NAME_UPDATE_GAME_QUEUE;
            $queueService->receive($this->getReceiver($queueService), $timeoutInSeconds, $queueName);
        } catch (\Exception $e) {
            if( $this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    protected function getReceiver(QueueService $queueService): callable
    {
        return function (Message $message, Consumer $consumer) use ($queueService) : void {
            // process message
            $this->logger->info('------ EXECUTING ------');
            try {
                $content = json_decode($message->getBody());
                $game = null;
                if (property_exists($content, "gameId")) {
                    $game = $this->gameRepos->find((int)$content->gameId);
                }
                $oldStartDateTime = null;
                if (property_exists($content, "oldTimestamp")) {
                    $oldStartDateTime = new \DateTimeImmutable("@" . $content->oldTimestamp );
                }
                if ($game !== null) {
                    $this->process($queueService, $game, $oldStartDateTime);
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

    /**
     * @param QueueService $queueService
     * @param Game $game
     * @param \DateTimeImmutable|null $oldDateTime
     * @throws Exception
     */
    protected function process( QueueService $queueService, Game $game, \DateTimeImmutable $oldDateTime = null ) {

        $competition = $game->getRound()->getNumber()->getCompetition();

        $viewPeriod = $this->viewPeriodRepos->findOneByDate( $competition, $game->getStartDateTime() );
        if( $viewPeriod === null ) {
            throw new \Exception("no viewperiod found for game", E_ERROR );
        }

        $gameRound = $this->gameRoundRepos->findOneByNumber( $competition, $game->getBatchNr() );
        if( $gameRound === null ) {
            $gameRound = new GameRound( $viewPeriod, $game->getBatchNr() );
            $this->gameRoundRepos->save($gameRound);
        }

        if( $oldDateTime === null || $viewPeriod->contains( $oldDateTime ) ) {
            return;
        }

        $gameRoundOwner = $this->viewPeriodRepos->findGameRoundOwner(
            $game->getPoule(),
            $game->getSportConfig(),
            $game->getBatchNr()
        );
        if( $gameRoundOwner === null || $viewPeriod === $gameRoundOwner ) {
            return;
        }
        $this->gameRoundRepos->remove($gameRound);
        $gameRound = new GameRound( $gameRoundOwner, $game->getBatchNr() );
        $this->gameRoundRepos->save($gameRound);
    }
}
