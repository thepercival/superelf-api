<?php

declare(strict_types=1);

namespace SuperElf\Calculator;

use App\Mailer;
use App\QueueService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game;
use Sports\Game\Score as GameScore;
use Sports\Game\Score\HomeAway as GameScoreHomeAway;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Participation as GameParticipation;
use Sports\Person;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Sport;
use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\Team;
use SuperElf\GameRound;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\View\Person as BaseViewPeriodPerson;
use SuperElf\Period\View\Person\GameRoundScore as ViewPeriodPersonGameRoundScore;
use SuperElf\Period\View\Person\GameRoundScore\Repository as ViewPeriodPersonGameRoundScoreRepository;
use SuperElf\Period\View\Person\Repository as ViewPeriodPersonRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Calculator\Substitute as PoolUserViewPeriodPersonCalculator;
use SuperElf\Season\ScoreUnit\Creator as ScoreUnitCreator;
use SuperElf\Season\ScoreUnit\Calculator as ScoreUnitCalculator;

class ViewPeriodPerson
{
    protected GameRoundRepository $gameRoundRepos;
    protected ViewPeriodPersonGameRoundScoreRepository $gameRoundScoreRepos;
    protected ViewPeriodPersonRepository $viewPeriodPersonRepos;
    protected ViewPeriodRepository $viewPeriodRepos;
    protected PoolUserViewPeriodPersonCalculator $substituteCalculator;
    protected ScoreUnitCreator $scoreUnitCreator;
    protected ScoreUnitCalculator $scoreUnitCalculator;

    public function __construct(
        GameRoundRepository $gameRoundRepos,
        ViewPeriodPersonGameRoundScoreRepository $gameRoundScoreRepos,
        ViewPeriodPersonRepository $viewPeriodPersonRepos,
        ViewPeriodRepository $viewPeriodRepos,
        PoolUserViewPeriodPersonCalculator $substituteCalculator,
        ScoreUnitCreator $scoreUnitCreator
    ) {
        $this->gameRoundRepos = $gameRoundRepos;
        $this->gameRoundScoreRepos = $gameRoundScoreRepos;
        $this->viewPeriodPersonRepos = $viewPeriodPersonRepos;
        $this->viewPeriodRepos = $viewPeriodRepos;
        $this->substituteCalculator = $substituteCalculator;
        $this->scoreUnitCreator = $scoreUnitCreator;
        $this->scoreUnitCalculator = new ScoreUnitCalculator();
    }

    public function calculate( Game $game) {
        $competition = $game->getRound()->getNumber()->getCompetition();
        $map = new PlaceLocationMap( $competition->getTeamCompetitors()->toArray() );

        $viewPeriod = $this->viewPeriodRepos->findOneByGameRoundNumber( $competition, $game->getBatchNr() );
        if( $viewPeriod === null ) {
            throw new \Exception("the viewperiod should be found for batchnr: " . $game->getBatchNr(), E_ERROR );
        }
        foreach( [Game::HOME, Game::AWAY] as $homeAway ) {
            foreach( $game->getCompetitors( $map, $homeAway ) as $teamCompetitor ) {
                if( !($teamCompetitor instanceof TeamCompetitor ) ) {
                    continue;
                }
                $this->createViewPeriodPersonsFromGameParticipations( $viewPeriod, $game, $teamCompetitor );

                $this->calculateViewPeriodPersonGameRounds( $viewPeriod, $game, $homeAway, $teamCompetitor );
            }
        }
    }

    protected function createViewPeriodPersonsFromGameParticipations( ViewPeriod $viewPeriod, Game $game, TeamCompetitor $teamCompetitor ) {
        $gameParticipations = $game->getParticipations( $teamCompetitor );
        foreach( $gameParticipations as $gameParticipation ) {
            $this->createViewPeriodPerson( $viewPeriod, $gameParticipation->getPlayer()->getPerson() );
        }
    }

    /**
     * @param ViewPeriod $viewPeriod
     * @param Person $person
     */
    protected function createViewPeriodPerson( ViewPeriod $viewPeriod, Person $person ) {

        $viewPeriodPerson = $this->viewPeriodPersonRepos->findOneBy( ["viewPeriod" => $viewPeriod, "person" => $person ]);
        if( $viewPeriodPerson !== null ) {
            return;
        }
        $viewPeriodPerson = new BaseViewPeriodPerson($viewPeriod, $person );
        $this->viewPeriodPersonRepos->save($viewPeriodPerson);
    }

    protected function calculateViewPeriodPersonGameRounds( ViewPeriod $viewPeriod, Game $game, bool $homeAway, TeamCompetitor $teamCompetitor ) {
        $competition = $game->getRound()->getNumber()->getCompetition();

        $sportScoreConfigService = new SportScoreConfigService();
        $finalScore = $sportScoreConfigService->getFinalScore($game);
        if( $finalScore === null ) {
            return;
        }
        $gameRound = $this->gameRoundRepos->findOneByNumber( $competition, $game->getBatchNr() );
        if( $gameRound === null ) {
            return;
        }
        $gameParticipations = $game->getParticipations( $teamCompetitor );
        $teamViewPeriodPersons = $this->viewPeriodPersonRepos->findByExt( $viewPeriod, $teamCompetitor->getTeam() );
        foreach( $teamViewPeriodPersons as $viewPeriodPerson ) {
            $gameParticipation = $this->getGameParticipation( $viewPeriodPerson, $gameParticipations->toArray() );
            $changedGameRoundScore = $this->calculateGameRoundScore( $viewPeriodPerson, $gameParticipation, $gameRound, $finalScore, $homeAway );
            if( $changedGameRoundScore !== null ) {
                $totals = $this->calculateTotals( $viewPeriodPerson );
                $viewPeriodPerson->setPoints( $totals );
                $viewPeriodPerson->setTotal( (int) array_sum($totals) );
                if( $gameParticipation === null ) {
                    $player = $viewPeriodPerson->getPerson()->getPlayer( $teamCompetitor->getTeam(), $game->getStartDateTime() );
                    $this->substituteCalculator->calculate( $viewPeriodPerson, $player->getLine(), $gameRound );
                }
            }
        }
    }

    protected function calculateGameRoundScore(
        BaseViewPeriodPerson $viewPeriodPerson, GameParticipation $gameParticipation,
        GameRound $gameRound, GameScoreHomeAway $finalScore, bool $homeAway ): ?ViewPeriodPersonGameRoundScore
    {

        $gameRoundScore = $this->createGameRoundScore( $viewPeriodPerson, $gameRound );

        $newStats = $this->getStats( $finalScore, $homeAway, $gameParticipation);
        $oldStats = $gameRoundScore->getStats();

        if( $this->statsAreEqual( $oldStats, $newStats) ) {
            return null;
        }
        $gameRoundScore->setStats( $newStats );

        $scoreUnits = $this->scoreUnitCreator->create( $viewPeriodPerson->getViewPeriod()->getSourceCompetition()->getSeason() );
        $points = $this->scoreUnitCalculator->getPoints( $newStats, $scoreUnits );
        $gameRoundScore->setPoints( $points );
        $gameRoundScore->setTotal( (int)array_sum($points) );
        // echo $competitionPerson->getId() . " => " . implode(",",$stats) .  PHP_EOL;
        return $this->gameRoundScoreRepos->save($gameRoundScore);
    }

    /**
     * @param BaseViewPeriodPerson $viewPeriodPerson
     * @return array|int[]
     */
    protected function calculateTotals(BaseViewPeriodPerson $viewPeriodPerson) : array {
        $seasonScoreUnits = $this->scoreUnitCreator->create( $viewPeriodPerson->getViewPeriod()->getSourceCompetition()->getSeason() );

        $totals = [];
        foreach( $viewPeriodPerson->getGameRoundScores() as $gameRoundScore ) {
            $gameRoundScorePoints = $gameRoundScore->getPoints();
            foreach ($seasonScoreUnits as $seasonScoreUnit) {
                if (!array_key_exists($seasonScoreUnit->getNumber(), $totals)) {
                    $totals[$seasonScoreUnit->getNumber()] = 0;
                }
                $totals[$seasonScoreUnit->getNumber()] += $gameRoundScorePoints[$seasonScoreUnit->getNumber()];
            }
        }
        return $totals;
    }

    /**
     * @param BaseViewPeriodPerson $viewPeriodPerson
     * @param array|GameParticipation[] $gameParticipations
     * @return GameParticipation|null
     */
    protected function getGameParticipation( BaseViewPeriodPerson $viewPeriodPerson, array $gameParticipations ): ?GameParticipation {
        $filtered = array_filter( $gameParticipations, function( GameParticipation $gameParticipation ) use($viewPeriodPerson) : bool {
            return $viewPeriodPerson->getPerson() === $gameParticipation->getPlayer()->getPerson();
        });
        if( count($filtered) > 0 ) {
            return reset($filtered);
        }
        return null;
    }

    /**
     * @param BaseViewPeriodPerson $viewPeriodPerson
     * @param GameRound $gameRound
     * @return ViewPeriodPersonGameRoundScore
     */
    protected function createGameRoundScore( BaseViewPeriodPerson $viewPeriodPerson, GameRound $gameRound ): ViewPeriodPersonGameRoundScore {
        $gameRoundScore = $this->gameRoundScoreRepos->findOneBy( [
            "viewPeriodPerson" => $viewPeriodPerson, "gameRound" => $gameRound ]);

        if( $gameRoundScore !== null ) {
            return $gameRoundScore;
        }
        $gameRoundScore = new ViewPeriodPersonGameRoundScore( $viewPeriodPerson, $gameRound );
        return $this->gameRoundScoreRepos->save($gameRoundScore);
    }


    /**
     * @param GameScoreHomeAway $finalScore
     * @param bool $homeAway
     * @param GameParticipation|null $participation
     * @return array
     */
    protected function getStats( GameScoreHomeAway $finalScore, bool $homeAway, GameParticipation $participation = null): array {
        if( $participation === null ) {
            return [
                BaseViewPeriodPerson::RESULT => Game::RESULT_LOST,
                BaseViewPeriodPerson::GOALS_FIELD => 0,
                BaseViewPeriodPerson::GOALS_PENALTY => 0,
                BaseViewPeriodPerson::GOALS_OWN => 0,
                BaseViewPeriodPerson::ASSISTS => 0,
                BaseViewPeriodPerson::SHEET_CLEAN => false,
                BaseViewPeriodPerson::SHEET_SPOTTY => false,
                BaseViewPeriodPerson::CARDS_YELLOW => 0,
                BaseViewPeriodPerson::CARD_RED => 0,
                BaseViewPeriodPerson::LINEUP => false,
                BaseViewPeriodPerson::SUBSTITUTED => false,
                BaseViewPeriodPerson::SUBSTITUTE => false,
                BaseViewPeriodPerson::LINE => 0
            ];
        }
        return [
            BaseViewPeriodPerson::RESULT => $finalScore->getResult($homeAway),
            BaseViewPeriodPerson::GOALS_FIELD => $participation->getGoals(GoalEvent::FIELD )->count(),
            BaseViewPeriodPerson::GOALS_PENALTY => $participation->getGoals(GoalEvent::PENALTY )->count(),
            BaseViewPeriodPerson::GOALS_OWN => $participation->getGoals(GoalEvent::OWN )->count(),
            BaseViewPeriodPerson::ASSISTS => $participation->getAssists()->count(),
            BaseViewPeriodPerson::SHEET_CLEAN => $finalScore->get(!$homeAway) === 0,
            BaseViewPeriodPerson::SHEET_SPOTTY => $finalScore->get(!$homeAway) >= BaseViewPeriodPerson::SHEET_SPOTTY_THRESHOLD,
            BaseViewPeriodPerson::CARDS_YELLOW => $participation->getCards(Sport::WARNING )->count(),
            BaseViewPeriodPerson::CARD_RED => $participation->getCards(Sport::SENDOFF )->count(),
            BaseViewPeriodPerson::LINEUP => !$participation->isBeginning(),
            BaseViewPeriodPerson::SUBSTITUTED => $participation->isSubstituted(),
            BaseViewPeriodPerson::SUBSTITUTE => $participation->isSubstituted(),
            BaseViewPeriodPerson::LINE => $participation->getPlayer()->getLine()
        ];
    }

    protected function statsAreEqual( array $oldStats, array $newStats) {
        return count($oldStats) === count($newStats)
            && $oldStats[BaseViewPeriodPerson::RESULT] === $newStats[BaseViewPeriodPerson::RESULT]
            && $oldStats[BaseViewPeriodPerson::GOALS_FIELD] === $newStats[BaseViewPeriodPerson::GOALS_FIELD]
            && $oldStats[BaseViewPeriodPerson::GOALS_PENALTY] === $newStats[BaseViewPeriodPerson::GOALS_PENALTY]
            && $oldStats[BaseViewPeriodPerson::GOALS_OWN] === $newStats[BaseViewPeriodPerson::GOALS_OWN]
            && $oldStats[BaseViewPeriodPerson::ASSISTS] === $newStats[BaseViewPeriodPerson::ASSISTS]
            && $oldStats[BaseViewPeriodPerson::SHEET_CLEAN] === $newStats[BaseViewPeriodPerson::SHEET_CLEAN]
            && $oldStats[BaseViewPeriodPerson::SHEET_SPOTTY] === $newStats[BaseViewPeriodPerson::SHEET_SPOTTY]
            && $oldStats[BaseViewPeriodPerson::CARDS_YELLOW] === $newStats[BaseViewPeriodPerson::CARDS_YELLOW]
            && $oldStats[BaseViewPeriodPerson::CARD_RED] === $newStats[BaseViewPeriodPerson::CARD_RED]
            && $oldStats[BaseViewPeriodPerson::LINEUP] === $newStats[BaseViewPeriodPerson::LINEUP]
            && $oldStats[BaseViewPeriodPerson::SUBSTITUTED] === $newStats[BaseViewPeriodPerson::SUBSTITUTED]
            && $oldStats[BaseViewPeriodPerson::SUBSTITUTE] === $newStats[BaseViewPeriodPerson::SUBSTITUTE]
            && $oldStats[BaseViewPeriodPerson::LINE] === $newStats[BaseViewPeriodPerson::LINE];
    }
}
