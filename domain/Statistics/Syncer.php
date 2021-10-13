<?php
declare(strict_types=1);

namespace SuperElf\Player;

use DateTime;
use Psr\Log\LoggerInterface;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Participation as GameParticipation;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Person;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Team;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Statistics\Repository as StatisticsRepository;
use SuperElf\Player as S11Player;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Substitute\Appearance\Calculator as SubstituteAppearanceCalculator;
use SuperElf\Points\Calculator as PointsCalculator;
use SuperElf\Points\Creator as PointsCreator;
use SportsHelpers\Against\Side as AgainstSide;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Output\Game\Against as AgainstGameOutput;
use Sports\Competitor\Map as CompetitorMap;

class Syncer
{
    protected LoggerInterface|null $logger = null;

    public function __construct(
        protected GameRoundRepository $gameRoundRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected StatisticsRepository $statisticsRepos,
        protected SubstituteAppearanceCalculator $appearanceCalculator,
        protected PointsCreator $pointsCreator,
        protected PointsCalculator $pointsCalculator
    ) {
    }

    public function calculate(AgainstGame $game): void
    {
        $competition = $game->getRound()->getNumber()->getCompetition();
        // viewperiods for season

        $competitors = array_values($competition->getTeamCompetitors()->toArray());
        $map = new CompetitorMap($competitors);
        $this->logGame($game, $map);
//
        $viewPeriod = $this->viewPeriodRepos->findOneByDate($competition, $game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new \Exception('the viewperiod should be found for date: ' . $game->getStartDateTime()->format(DateTime::ISO8601), E_ERROR);
        }
        // foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $homeAway) {
        foreach ($game->getPlaces(/*$homeAway*/) as $gamePlace) {
            $teamCompetitor = $map->getCompetitor($gamePlace->getPlace());
            if (!($teamCompetitor instanceof TeamCompetitor)) {
                continue;
            }

            $this->validateS11Players($viewPeriod, $gamePlace);

            $this->calculateStatistics($viewPeriod, $gamePlace, $teamCompetitor->getTeam());
        }
        // }
    }

    protected function validateS11Players(ViewPeriod $viewPeriod, AgainstGamePlace $gamePlace): void
    {
        $this->logInfo('validating s11Players ..');
        foreach ($gamePlace->getParticipations() as $gameParticipation) {
            $this->validateS11Player($viewPeriod, $gameParticipation->getPlayer()->getPerson());
        }
        $this->logInfo('validated s11Players');
    }

    protected function validateS11Player(ViewPeriod $viewPeriod, Person $person): void
    {
        $s11Player = $this->s11PlayerRepos->findOneBy(["viewPeriod" => $viewPeriod, "person" => $person ]);
        if ($s11Player !== null) {
            return;
        }
        $s11Player = new S11Player($viewPeriod, $person);
        ;
        $this->logCreateS11Player($this->s11PlayerRepos->save($s11Player));
    }

    protected function calculateStatistics(
        ViewPeriod $viewPeriod,
        AgainstGamePlace $gamePlace,
        Team $team
    ): void {
        $this->logInfo('calculating statistics ..');
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
        // $seasonPoints = $this->scoreUnitCreator->create($viewPeriod->getSourceCompetition()->getSeason());
        $gameParticipations = array_values($gamePlace->getParticipations()->toArray());
        $s11Players = $this->s11PlayerRepos->findByExt($viewPeriod, $team);
        foreach ($s11Players as $s11Player) {
            $person = $s11Player->getPerson();
            $player = $person->getPlayer($team, $game->getStartDateTime());
            if ($player === null) {
                $this->logNoS11Player($person);
                continue;
            }
            $gameParticipation = $this->getGameParticipation($s11Player, $gameParticipations);
            $statistics = $s11Player->getGameRoundStatistics($gameRound);
            // if ($gameParticipation === null) {
            if ($statistics !== null) {
                $s11Player->getStatistics()->removeElement($statistics);
                $this->statisticsRepos->remove($statistics);
            }
                continue;
            }
            $statistics = $this->converter->convert() $s11Player->getStatistics()->removeElement($statistics);
            $this->statisticsRepos->save($statistics);



//            $changedGameRoundScore = $this->calculateGameRoundScore($player, $gameRound, $finalScore, $side, $gameParticipation);
//            if ($changedGameRoundScore === null) {
//                continue;
//            }
//            $points = $player->calculatePoints($seasonScoreUnits);
//            $s11Player->setPoints($points);
//            $s11Player->setTotal(array_sum($points));
//            $this->s11PlayerRepos->save($s11Player);
//            $this->appearanceCalculator->calculate($s11Player, $player->getLine(), $gameRound, $seasonScoreUnits);
        }
        $this->logInfo('calculated statistics ..');
    }

    protected function calculateSubstituteAppearances(
        ViewPeriod $viewPeriod,
        AgainstGamePlace $gamePlace,
        Team $team
    ): void {
        $this->logInfo('calculating substitute-appearances ..');
        // @TODO CDK THROUGH UPDATE STATEMENT???
        $this->logInfo('calculated substitute-appearances ..');
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
        $filtered = array_filter($gameParticipations, function (GameParticipation $gameParticipation) use ($s11Player) : bool {
            return $s11Player->getPerson() === $gameParticipation->getPlayer()->getPerson();
        });
        if (count($filtered) > 0) {
            return reset($filtered);
        }
        return null;
    }
//
//    /**
//     * @param BaseViewPeriodPerson $viewPeriodPerson
//     * @param GameRound $gameRound
//     * @return ViewPeriodPersonGameRoundScore
//     */
//    protected function createGameRoundScore( BaseViewPeriodPerson $viewPeriodPerson, GameRound $gameRound ): ViewPeriodPersonGameRoundScore {
//        $gameRoundScore = $this->gameRoundScoreRepos->findOneBy( [
//            "viewPeriodPerson" => $viewPeriodPerson, "gameRound" => $gameRound ]);
//
//        if( $gameRoundScore !== null ) {
//            return $gameRoundScore;
//        }
//        $gameRoundScore = new ViewPeriodPersonGameRoundScore( $viewPeriodPerson, $gameRound );
//        return $this->gameRoundScoreRepos->save($gameRoundScore);
//    }
//
//
//    protected function getStats(
//        AgainstScoreHelper $finalScore,
//        int $side,
//        GameParticipation $participation = null): ParticipationStats|null {
//        if( $participation === null ) {
//            return null;
//        }
//        $opposite = $side === Side::HOME ? Side::AWAY : Side::HOME;
//        return new ParticipationStats(
//            $finalScore->getResult($side),
//            $participation->getGoals(GoalEvent::FIELD )->count(),
//            $participation->getGoals(GoalEvent::PENALTY )->count(),
//            $participation->getGoals(GoalEvent::OWN )->count(),
//            $participation->getAssists()->count(),
//            $finalScore->get($opposite) === 0,
//            $finalScore->get($opposite) >= BaseViewPeriodPerson::SHEET_SPOTTY_THRESHOLD,
//            BaseViewPeriodPerson::CARDS_YELLOW => $participation->getCards(Sport::WARNING )->count(),
//            BaseViewPeriodPerson::CARD_RED => $participation->getCards(Sport::SENDOFF )->count(),
//            BaseViewPeriodPerson::LINEUP => !$participation->isBeginning(),
//            BaseViewPeriodPerson::SUBSTITUTED => $participation->isSubstituted(),
//            BaseViewPeriodPerson::SUBSTITUTE => $participation->getM
//
//        ];
//    }
//
//    protected function statsAreEqual( array $oldStats, array $newStats): bool {
//        return count($oldStats) === count($newStats)
//            && $oldStats[BaseViewPeriodPerson::RESULT] === $newStats[BaseViewPeriodPerson::RESULT]
//            && $oldStats[BaseViewPeriodPerson::GOALS_FIELD] === $newStats[BaseViewPeriodPerson::GOALS_FIELD]
//            && $oldStats[BaseViewPeriodPerson::GOALS_PENALTY] === $newStats[BaseViewPeriodPerson::GOALS_PENALTY]
//            && $oldStats[BaseViewPeriodPerson::GOALS_OWN] === $newStats[BaseViewPeriodPerson::GOALS_OWN]
//            && $oldStats[BaseViewPeriodPerson::ASSISTS] === $newStats[BaseViewPeriodPerson::ASSISTS]
//            && $oldStats[BaseViewPeriodPerson::SHEET_CLEAN] === $newStats[BaseViewPeriodPerson::SHEET_CLEAN]
//            && $oldStats[BaseViewPeriodPerson::SHEET_SPOTTY] === $newStats[BaseViewPeriodPerson::SHEET_SPOTTY]
//            && $oldStats[BaseViewPeriodPerson::CARDS_YELLOW] === $newStats[BaseViewPeriodPerson::CARDS_YELLOW]
//            && $oldStats[BaseViewPeriodPerson::CARD_RED] === $newStats[BaseViewPeriodPerson::CARD_RED]
//            && $oldStats[BaseViewPeriodPerson::LINEUP] === $newStats[BaseViewPeriodPerson::LINEUP]
//            && $oldStats[BaseViewPeriodPerson::SUBSTITUTED] === $newStats[BaseViewPeriodPerson::SUBSTITUTED]
//            && $oldStats[BaseViewPeriodPerson::SUBSTITUTE] === $newStats[BaseViewPeriodPerson::SUBSTITUTE]
//            && $oldStats[BaseViewPeriodPerson::LINE] === $newStats[BaseViewPeriodPerson::LINE];
//    }
//
//    public function setLogger( LoggerInterface $logger ): void {
//        $this->logger = $logger;
//    }
//
    protected function logGame(AgainstGame $game, CompetitorMap $competitorMap): void
    {
        if ($this->logger === null) {
            return;
        }
        $gameOutput = new AgainstGameOutput($competitorMap);
        $gameOutput->output($game);
    }

    protected function logCreateS11Player(S11Player $s11Player): void
    {
        $basePeriod = $s11Player->getViewPeriod()->getPeriod();
        $periodDescription = "periode " . $basePeriod->getStartDate()->format("Y-m-d"). " t/m " . $basePeriod->getEndDate()->format("Y-m-d");
        $this->logInfo("  toegevoegd: " . $periodDescription ." , persoon: " . $s11Player->getPerson()->getName());
    }

    protected function logNoS11Player(Person $person): void
    {
        $this->logInfo("  voor persoon: " . $person->getName() . " is geen speler gevonden");
        foreach ($person->getPlayers() as $playerIt) {
            $basePeriod = $playerIt->getPeriod();
            $this->logInfo("      playerinfo: " . $playerIt->getTeam()->getName() . " (".$playerIt->getLine().") => periode " . $basePeriod);
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
