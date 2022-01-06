<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Season;
use Sports\Sport;
use SportsHelpers\Identifiable;

class Points extends Identifiable
{
    public function __construct(
        protected Season $season,
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

    public function getFieldGoal(int $line): int
    {
        switch ($line) {
            case Sport\Custom::Football_Line_GoalKepeer:
                return $this->getFieldGoalGoalkeeper();
            case Sport\Custom::Football_Line_Defense:
                return $this->getFieldGoalDefender();
            case Sport\Custom::Football_Line_Midfield:
                return $this->getFieldGoalMidfielder();
            case Sport\Custom::Football_Line_Forward:
                return $this->getFieldGoalForward();
        }
        throw new \Exception('incorrect line('.$line.') to get fieldGoalPoints', E_ERROR);
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

    public function getAssist(int $line): int
    {
        switch ($line) {
            case Sport\Custom::Football_Line_GoalKepeer:
                return $this->getAssistGoalkeeper();
            case Sport\Custom::Football_Line_Defense:
                return $this->getAssistDefender();
            case Sport\Custom::Football_Line_Midfield:
                return $this->getAssistMidfielder();
            case Sport\Custom::Football_Line_Forward:
                return $this->getAssistForward();
        }
        throw new \Exception('incorrect line('.$line.') to get assists', E_ERROR);
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

    public function getCleanSheet(int $line): int
    {
        if ($line === Sport\Custom::Football_Line_GoalKepeer) {
            return $this->getCleanSheetGoalkeeper();
        } elseif ($line === Sport\Custom::Football_Line_Defense) {
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

    public function getSpottySheet(int $line): int
    {
        if ($line === Sport\Custom::Football_Line_GoalKepeer) {
            return $this->getSpottySheetGoalkeeper();
        } elseif ($line === Sport\Custom::Football_Line_Defense) {
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

    public function getSeason(): Season
    {
        return $this->season;
    }
}
