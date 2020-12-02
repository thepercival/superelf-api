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
use SuperElf\CompetitionPerson;
use Sports\Game\Repository as GameRepository;
use SuperElf\CompetitionPerson\Repository as CompetitionPersonRepository;
use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Competitor\Team as TeamCompetitor;
use SuperElf\GameRound;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\CompetitionPerson\GameRoundScore;
use SuperElf\CompetitionPerson\GameRoundScore\Repository as GameRoundScoreRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;

class PersonStats extends Command
{
    protected GameRepository $gameRepos;
    protected GameRoundRepository $gameRoundRepos;
    protected GameRoundScoreRepository $gameRoundScoreRepos;
    protected ViewPeriodRepository $viewPeriodRepos;
    protected CompetitionPersonRepository $competitionPersonRepos;
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
        $this->gameRoundScoreRepos = $container->get(GameRoundScoreRepository::class);
        $this->viewPeriodRepos = $container->get(ViewPeriodRepository::class);
        $this->competitionPersonRepos = $container->get(CompetitionPersonRepository::class);
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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLogger($input, 'command-personstats-calculate');
        $this->logger->info('starting command app:calculate-personstats');

        try {
            $queueService = new QueueService($this->config->getArray('queue'));
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
        $map = new PlaceLocationMap( $competition->getTeamCompetitors()->toArray() );

        foreach( [Game::HOME, Game::AWAY] as $homeAway ) {


            foreach( $game->getCompetitors( $map, $homeAway ) as $teamCompetitor ) {
                if( !($teamCompetitor instanceof TeamCompetitor ) ) {
                    continue;
                }
                $participations = $game->getParticipations( $teamCompetitor );
                foreach( $participations as $participation ) {
                    $competitionPerson = $this->competitionPersonRepos->findOneBy( [
                                                                                       "sourceCompetition" => $competition,
                                                                                       "person" => $participation->getPlayer()->getPerson() ] );
                    if( $competitionPerson === null ) {
                        $competitionPerson = new CompetitionPerson( $competition, $participation->getPlayer()->getPerson() );
                        $this->competitionPersonRepos->save($competitionPerson);
                    }

                    $stats = [
                        CompetitionPerson::RESULT => $finalScore->getResult($homeAway),
                        CompetitionPerson::GOALS_FIELD => $participation->getGoals( GoalEvent::FIELD )->count(),
                        CompetitionPerson::GOALS_PENALTY => $participation->getGoals( GoalEvent::PENALTY )->count(),
                        CompetitionPerson::GOALS_OWN => $participation->getGoals( GoalEvent::OWN )->count(),
                        CompetitionPerson::ASSISTS => $participation->getAssists()->count(),
                        CompetitionPerson::SHEET_CLEAN => $finalScore->get(!$homeAway) === 0,
                        CompetitionPerson::SHEET_SPOTTY => $finalScore->get(!$homeAway) >= CompetitionPerson::SHEET_SPOTTY_THRESHOLD,
                        CompetitionPerson::CARDS_YELLOW => $participation->getCards( Sport::WARNING )->count(),
                        CompetitionPerson::CARD_RED => $participation->getCards( Sport::SENDOFF )->count(),
                        CompetitionPerson::LINEUP => !$participation->isBeginning(),
                        CompetitionPerson::SUBSTITUTED => $participation->isSubstituted(),
                        CompetitionPerson::SUBSTITUTE => $participation->isSubstituted(),
                        CompetitionPerson::LINE => $participation->getPlayer()->getLine()
                    ];

                    $gameRound = $this->gameRoundRepos->findOneByNumber( $competition, $game->getBatchNr() );
                    if( $gameRound === null ) {
                        continue;
                    }

                    $gameRoundScore = $this->gameRoundScoreRepos->findOneByCustom(
                        $competition, $participation->getPlayer()->getPerson(), $game->getBatchNr() );

                    if( $gameRoundScore === null ) {
                        $gameRoundScore = new GameRoundScore( $competitionPerson, $gameRound );
                    }

                    $gameRoundScore->setDetailedPoints( $stats );
                    // echo $competitionPerson->getId() . " => " . implode(",",$stats) .  PHP_EOL;
                    $this->gameRoundScoreRepos->save($gameRoundScore);
                }
            }
        }
    }
}
