<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Sport\FootballLine;
use SportsHelpers\Identifiable;
use SuperElf\Score\LinePoints as LineScorePoints;
use SuperElf\Score\Points as ScorePoints;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Points extends Identifiable
{
    /**
     * @var array<string, ScorePoints>
     */
    protected array|null $scorePoints = null;
    /**
     * @var array<string, LineScorePoints>
     */
    protected array|null $lineScorePoints = null;

    public function __construct(
        protected int $resultWin,
        protected int $resultDraw,
        protected int $fieldGoalGoalkeeper,
        protected int $fieldGoalDefender,
        protected int $fieldGoalMidfielder,
        protected int $fieldGoalForward,
        protected int $assistGoalkeeper,
        protected int $assistDefender,
        protected int $assistMidfielder,
        protected int $assistForward,
        protected int $penalty,
        protected int $ownGoal,
        protected int $cleanSheetGoalkeeper,
        protected int $cleanSheetDefender,
        protected int $spottySheetGoalkeeper,
        protected int $spottySheetDefender,
        protected int $cardYellow,
        protected int $cardRed
    ) {
    }

    protected function addLineScorePoints(FootballLine $line, FootballScore $score): void
    {
        $lineScorePoints = $this->getOneLineScorePoints($line, $score);
        $this->lineScorePoints[$lineScorePoints->getId()] = $lineScorePoints;
    }

    public function getResultWin(): int
    {
        return $this->resultWin;
    }

    public function getResultDraw(): int
    {
        return $this->resultDraw;
    }

    public function getFieldGoalGoalkeeper(): int
    {
        return $this->fieldGoalGoalkeeper;
    }

    public function getFieldGoalDefender(): int
    {
        return $this->fieldGoalDefender;
    }

    public function getFieldGoalMidfielder(): int
    {
        return $this->fieldGoalMidfielder;
    }

    public function getFieldGoalForward(): int
    {
        return $this->fieldGoalForward;
    }

    public function getFieldGoal(FootballLine $line): int
    {
        switch ($line) {
            case FootballLine::GoalKeeper:
                return $this->getFieldGoalGoalkeeper();
            case FootballLine::Defense:
                return $this->getFieldGoalDefender();
            case FootballLine::Midfield:
                return $this->getFieldGoalMidfielder();
            case FootballLine::Forward:
                return $this->getFieldGoalForward();
        }
        throw new \Exception('incorrect line(' . $line->name . ') to get fieldGoalPoints', E_ERROR);
    }

    public function getAssistGoalkeeper(): int
    {
        return $this->assistGoalkeeper;
    }

    public function getAssistDefender(): int
    {
        return $this->assistDefender;
    }

    public function getAssistMidfielder(): int
    {
        return $this->assistMidfielder;
    }

    public function getAssistForward(): int
    {
        return $this->assistForward;
    }

    public function getAssist(FootballLine $line): int
    {
        switch ($line) {
            case FootballLine::GoalKeeper:
                return $this->getAssistGoalkeeper();
            case FootballLine::Defense:
                return $this->getAssistDefender();
            case FootballLine::Midfield:
                return $this->getAssistMidfielder();
            case FootballLine::Forward:
                return $this->getAssistForward();
        }
        throw new \Exception('incorrect line(' . $line->name . ') to get assists', E_ERROR);
    }

    public function getPenalty(): int
    {
        return $this->penalty;
    }

    public function getOwnGoal(): int
    {
        return $this->ownGoal;
    }

    public function getCleanSheetGoalkeeper(): int
    {
        return $this->cleanSheetGoalkeeper;
    }

    public function getCleanSheetDefender(): int
    {
        return $this->cleanSheetDefender;
    }

    public function getCleanSheet(FootballLine $line): int
    {
        if ($line === FootballLine::GoalKeeper) {
            return $this->getCleanSheetGoalkeeper();
        } elseif ($line === FootballLine::Defense) {
            return $this->getCleanSheetDefender();
        }
        return 0;
    }

    public function getSpottySheetGoalkeeper(): int
    {
        return $this->spottySheetGoalkeeper;
    }

    public function getSpottySheetDefender(): int
    {
        return $this->spottySheetDefender;
    }

    public function getSpottySheet(FootballLine $line): int
    {
        if ($line === FootballLine::GoalKeeper) {
            return $this->getSpottySheetGoalkeeper();
        } elseif ($line === FootballLine::Defense) {
            return $this->getSpottySheetDefender();
        }
        return 0;
    }

    public function getCardYellow(): int
    {
        return $this->cardYellow;
    }

    public function getCardRed(): int
    {
        return $this->cardRed;
    }

    /**
     * @return list<ScorePoints>
     */
    public function getScorePoints(): array
    {
        return array_values($this->getScorePointsHelper());
    }

    /**
     * @return array<string, ScorePoints>
     */
    private function getScorePointsHelper(): array
    {
        if ($this->scorePoints !== null) {
            return $this->scorePoints;
        }
        $this->scorePoints = [
            FootballScore::WinResult->value => new ScorePoints(FootballScore::WinResult, $this->resultWin),
            FootballScore::DrawResult->value => new ScorePoints(FootballScore::DrawResult, $this->resultDraw),
            FootballScore::PenaltyGoal->value => new ScorePoints(FootballScore::PenaltyGoal, $this->penalty),
            FootballScore::OwnGoal->value => new ScorePoints(FootballScore::OwnGoal, $this->ownGoal),
            FootballScore::YellowCard->value => new ScorePoints(FootballScore::YellowCard, $this->cardYellow),
            FootballScore::RedCard->value => new ScorePoints(FootballScore::RedCard, $this->cardRed)
        ];
        return $this->scorePoints;
    }

    public function getScorePointsAsValue(FootballScore $score): int
    {
        if ($score === FootballScore::WinResult) {
            return $this->getResultWin();
        } elseif ($score === FootballScore::DrawResult) {
            return $this->getResultDraw();
        } elseif ($score === FootballScore::PenaltyGoal) {
            return $this->getPenalty();
        } elseif ($score === FootballScore::OwnGoal) {
            return $this->getOwnGoal();
        } elseif ($score === FootballScore::YellowCard) {
            return $this->getCardYellow();
        } elseif ($score === FootballScore::RedCard) {
            return $this->getCardRed();
        }
        throw new \Exception('unknown score', E_ERROR);
    }

    /**
     * @return list<LineScorePoints>
     */
    public function getLineScorePoints(): array
    {
        return array_values($this->getLineScorePointsHelper());
    }

    /**
     * @return array<string, LineScorePoints>
     */
    private function getLineScorePointsHelper(): array
    {
        if ($this->lineScorePoints !== null) {
            return $this->lineScorePoints;
        }
        $this->lineScorePoints = [];
        $this->addLineScorePoints(FootballLine::GoalKeeper, FootballScore::Goal);
        $this->addLineScorePoints(FootballLine::GoalKeeper, FootballScore::Assist);
        $this->addLineScorePoints(FootballLine::GoalKeeper, FootballScore::CleanSheet);
        $this->addLineScorePoints(FootballLine::GoalKeeper, FootballScore::SpottySheet);
        $this->addLineScorePoints(FootballLine::Defense, FootballScore::Goal);
        $this->addLineScorePoints(FootballLine::Defense, FootballScore::Assist);
        $this->addLineScorePoints(FootballLine::Defense, FootballScore::CleanSheet);
        $this->addLineScorePoints(FootballLine::Defense, FootballScore::SpottySheet);
        $this->addLineScorePoints(FootballLine::Midfield, FootballScore::Goal);
        $this->addLineScorePoints(FootballLine::Midfield, FootballScore::Assist);
        $this->addLineScorePoints(FootballLine::Forward, FootballScore::Goal);
        $this->addLineScorePoints(FootballLine::Forward, FootballScore::Assist);
        return $this->lineScorePoints;
    }

    protected function getOneLineScorePoints(FootballLine $line, FootballScore $score): LineScorePoints
    {
        $points = $this->getLineScorePointsAsValue($line, $score);
        return new LineScorePoints($line, $score, $points);
    }

    public function getLineScorePointsAsValue(FootballLine $line, FootballScore $score): int
    {
        if ($score === FootballScore::Goal) {
            return $this->getFieldGoal($line);
        } elseif ($score === FootballScore::Assist) {
            return $this->getAssist($line);
        } elseif ($score === FootballScore::CleanSheet) {
            return $this->getCleanSheet($line);
        } elseif ($score === FootballScore::SpottySheet) {
            return $this->getSpottySheet($line);
        }
        throw new \Exception('unknown line-score', E_ERROR);
    }
}
