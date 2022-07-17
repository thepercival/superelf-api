<?php

declare(strict_types=1);

namespace SuperElf\Statistics;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Sports\Competitor\StartLocationMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Participation as GameParticipation;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Person;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Team;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Player as S11Player;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player\Totals\Calculator as PlayerTotalsCalculator;
use SuperElf\Points;
use SuperElf\Points\Calculator as PointsCalculator;
use SuperElf\Points\Creator as PointsCreator;
use SuperElf\Statistics\Repository as StatisticsRepository;

class Syncer
{
    protected PlayerTotalsCalculator $playerTotalsCalculator;
    protected LoggerInterface|null $logger = null;

    public function __construct(
        protected GameRoundRepository $gameRoundRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected CompetitionConfigRepository $competitionConfigRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected PointsCreator $pointsCreator,
        protected StatisticsRepository $statisticsRepos,
        protected Converter $converter,
        protected PointsCalculator $pointsCalculator
    ) {
        $this->playerTotalsCalculator = new PlayerTotalsCalculator();
    }

    public function sync(CompetitionConfig $competitionConfig, AgainstGame $game): void
    {
        $competition = $game->getRound()->getNumber()->getCompetition();
        if ($competitionConfig->getSourceCompetition() !== $competition) {
            throw new Exception('the game is from another competitonconfig', E_ERROR);
        }

        $points = $competitionConfig->getPoints();
        $competitors = array_values($competition->getTeamCompetitors()->toArray());
        $map = new StartLocationMap($competitors);
//
        $viewPeriod = $competitionConfig->getViewPeriodByDate($game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new \Exception(
                'the viewperiod should be found for date: ' . $game->getStartDateTime()->format(DateTime::ISO8601),
                E_ERROR
            );
        }
        // foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $homeAway) {
        foreach ($game->getPlaces(/*$homeAway*/) as $gamePlace) {
            $startLocation = $gamePlace->getPlace()->getStartLocation();
            if ($startLocation === null) {
                continue;
            }
            $teamCompetitor = $map->getCompetitor($startLocation);
            if (!($teamCompetitor instanceof TeamCompetitor)) {
                continue;
            }
            $this->syncStatistics($viewPeriod, $points, $gamePlace, $teamCompetitor->getTeam());
        }
        // }
//        $this->s11PlayerRepos->flush();
//        $this->statisticsRepos->flush();
    }

    protected function syncStatistics(
        ViewPeriod $viewPeriod,
        Points $points,
        AgainstGamePlace $gamePlace,
        Team $team
    ): void {
        $this->logInfo('calculating statistics ' . $team->getName() . ' ..');
        $game = $gamePlace->getGame();
        // $competition = $game->getRound()->getNumber()->getCompetition();

        $finalScore = (new ScoreConfigService())->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return;
        }
        $gameRound = $viewPeriod->getGameRound($game->getGameRoundNumber());
        // $gameRound = $this->gameRoundRepos->findOneByNumber($competition, $game->getBatchNr());
        if ($gameRound === null) {
            return;
        }
        $gameParticipations = array_values($gamePlace->getParticipations()->toArray());
        $s11Players = $this->s11PlayerRepos->findByExt($viewPeriod, $team);
        foreach ($s11Players as $s11Player) {
            $person = $s11Player->getPerson();
            $player = $person->getPlayer($team, $game->getStartDateTime());
            if ($player === null) {
                $this->logNoS11Player($person, $team, $game->getStartDateTime());
                continue;
            }
            $gameParticipation = $this->getGameParticipation($s11Player, $gameParticipations);
            $oldStatistics = $s11Player->getGameRoundStatistics($gameRound);

            if ($oldStatistics !== null) {
                $s11Player->getStatistics()->removeElement($oldStatistics);
                $this->statisticsRepos->remove($oldStatistics, true);
            }

            $statistics = $this->converter->convert(
                $viewPeriod,
                $s11Player,
                $gamePlace->getGame(),
                $gameParticipation
            );
            $this->statisticsRepos->save($statistics, true);

            if ($oldStatistics === null || !$statistics->equals($oldStatistics)) {
                $this->playerTotalsCalculator->updateTotals($s11Player);
                $this->playerTotalsCalculator->updateTotalPoints($points, $s11Player);
                $this->s11PlayerRepos->save($s11Player, true);
            }
        }
        $this->logInfo('calculated statistics');
    }

//
//    protected function calculateGameRoundScore(
//        BaseViewPeriodPerson $viewPeriodPerson, GameRound $gameRound,
//        AgainstScoreHelper $finalScore,
//        int $side, GameParticipation
//        $gameParticipation = null ): ?ViewPeriodPersonGameRoundScore
//    {
//
//        $gameRoundScore = $this->createGameRoundScore( $viewPeriodPerson, $gameRound );
//
//        $newStats = $this->getStats( $finalScore, $side, $gameParticipation);
//        $oldStats = $gameRoundScore->getStats();
//
//        if( $this->statsAreEqual( $oldStats, $newStats) ) {
//            return null;
//        }
//        $gameRoundScore->setStats( $newStats );
//
//        $scoreUnits = $this->scoreUnitCreator->create( $viewPeriodPerson->getViewPeriod()->getSourceCompetition()->getSeason() );
//        $points = $this->scoreUnitCalculator->getPoints( $newStats, $scoreUnits );
//        $gameRoundScore->setPoints( $points );
//        $gameRoundScore->setTotal( array_sum($points) );
//        // echo $competitionPerson->getId() . " => " . implode(",",$stats) .  PHP_EOL;
//        return $this->gameRoundScoreRepos->save($gameRoundScore);
//    }
//
    /**
     * @param S11Player $s11Player
     * @param list<GameParticipation> $gameParticipations
     * @return GameParticipation|null
     */
    protected function getGameParticipation(S11Player $s11Player, array $gameParticipations): GameParticipation|null
    {
        $filtered = array_filter($gameParticipations, function (GameParticipation $gameParticipation) use ($s11Player): bool {
            return $s11Player->getPerson() === $gameParticipation->getPlayer()->getPerson();
        });
        if (count($filtered) > 0) {
            return reset($filtered);
        }
        return null;
    }

    protected function logNoS11Player(Person $person, Team $team, \DateTimeImmutable $dateTime): void
    {
        $this->logInfo(
            '  voor "' . $person->getName() . '" en team "' . $team->getName() . '" op "' . $dateTime->format(
                'Y-m-d'
            ) . '" is geen spelersperiode gevonden'
        );
        foreach ($person->getPlayers() as $playerIt) {
            $basePeriod = $playerIt->getPeriod()->toIso80000('Y-m-d');
            $this->logInfo(
                "      playerinfo: " . $playerIt->getTeam()->getName() . " (" . $playerIt->getLine(
                ) . ") => periode " . $basePeriod
            );
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function logInfo(string $info): void
    {
        if ($this->logger === null) {
            return;
        }
        $this->logger->info($info);
    }
}
