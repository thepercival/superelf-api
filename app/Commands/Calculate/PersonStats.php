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
use SuperElf\PersonStats as PersonStatsBase;
use Sports\Game\Repository as GameRepository;
use SuperElf\PersonStats\Repository as PersonStatsRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Competitor\Team as TeamCompetitor;

class PersonStats extends Command
{
    protected GameRepository $gameRepos;
    protected PersonStatsRepository $personStatsRepos;
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
        $this->personStatsRepos = $container->get(PersonStatsRepository::class);
        $this->viewPeriodRepos = $container->get(ViewPeriodRepository::class);
        $this->entityManager = $container->get(EntityManager::class);
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

        $this->addArgument('gameId', InputArgument::OPTIONAL, 'game-id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLogger($input, 'command-personstats-calculate');
        $this->logger->info('starting command app:calculate-personstats');

        try {
            $queueService = new QueueService($this->config->getArray('queue'));
            if ($input->getArgument('gameId') !== null && strlen($input->getArgument('gameId')) > 0) {
                $game = $this->gameRepos->find((int)$input->getArgument('gameId'));
                if ($game === null) {
                    $this->logger->info('game ' . $input->getArgument('gameId') . ' not found');
                    return 0;
                }
                $this->processGame($queueService, $game);
                $this->logger->info('personstats for gameid ' . $input->getArgument('gameId') . ' created');
                return 0;
            }

            $timeoutInSeconds = 295;

            $queueName = QueueService::NAME_UPDATE_GAMEDETAILS_QUEUE;
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
                $content = json_decode($message->getBody());
                $game = null;
                if (property_exists($content, "gameId")) {
                    $game = $this->gameRepos->find((int)$content->gameId);
                }
                if ($game !== null) {
                    $this->processGame($queueService, $game);
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

    protected function processGame( QueueService $queueService, Game $game) {
        $competition = $game->getRound()->getNumber()->getCompetition();
        // $planningOutput = new PlanningOutput($this->logger);
        $sportScoreConfigService = new SportScoreConfigService();
        $finalScore = $sportScoreConfigService->getFinalScore($game);
        if( $finalScore === null ) {
            return;
        }
        $viewPeriod  = $this->viewPeriodRepos->findOneByGame( $game );
        if( $viewPeriod === null ) {
            return;
        }
        // ophalen via $competition en $game->getStartDateTime via customFind
        // findOneExt

        foreach( [Game::HOME, Game::AWAY] as $homeAway ) {
            foreach( $game->getCompetitors( new PlaceLocationMap( $competition->getTeamCompetitors()->toArray() ) ) as $teamCompetitor ) {
                if( !($teamCompetitor instanceof TeamCompetitor ) ) {
                    continue;
                }
                $participations = $game->getParticipations( $teamCompetitor );
                foreach( $participations as $participation ) {
                    $stats = [
                        PersonStatsBase::POINTS_WIN => $finalScore->getResult($homeAway) === Game::RESULT_WIN ? 1 : 0,
                        PersonStatsBase::POINTS_DRAW => $finalScore->getResult($homeAway) === Game::RESULT_DRAW ? 1 : 0,
                        PersonStatsBase::GOALS_FIELD => $participation->getGoals( GoalEvent::FIELD )->count(),
                        PersonStatsBase::GOALS_PENALTY => $participation->getGoals( GoalEvent::PENALTY )->count(),
                        PersonStatsBase::GOALS_OWN => $participation->getGoals( GoalEvent::OWN )->count(),
                        PersonStatsBase::ASSISTS => $participation->getAssists()->count(),
                        PersonStatsBase::SHEET_CLEAN => $finalScore->get(!$homeAway) === 0 ? 1 : 0,
                        PersonStatsBase::SHEET_SPOTTY => $finalScore->get(!$homeAway) >= PersonStatsBase::SHEET_SPOTTY_THRESHOLD ? 1 : 0,
                        PersonStatsBase::CARDS_YELLOW => $participation->getCards( Sport::WARNING )->count(),
                        PersonStatsBase::CARD_RED => $participation->getCards( Sport::SENDOFF )->count(),
                        PersonStatsBase::LINEUP => !$participation->isBeginning() ? 1 : 0,
                        PersonStatsBase::SUBSTITUTED => $participation->isSubstituted() ? 1 : 0,
                        PersonStatsBase::SUBSTITUTE => $participation->isSubstituted() ? 1 : 0
                    ];

                    $oldPersonStats = $this->personStatsRepos->findOneBy( [
                        "person" => $participation->getPlayer()->getPerson(),
                        "viewPeriod" => $viewPeriod,
                        "gameRound" => $game->getBatchNr() ] );
                    if( $oldPersonStats !== null ) {
                        $this->personStatsRepos->remove($oldPersonStats);
                    }
                    $personStats = new PersonStatsBase( $participation->getPlayer()->getPerson(), $stats, $viewPeriod );
                    $personStats->setGameRound( $game->getBatchNr() );
                    $this->personStatsRepos->save($personStats);
                }
            }
        }
    }
}
