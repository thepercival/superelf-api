<?php
declare(strict_types=1);

namespace SuperElf;

use Sports\Season;
use SportsHelpers\Identifiable;

class Points extends Identifiable
{
    public function __construct(
        protected Season $season,
        protected int $resultWin,
        protected int $resultDraw,
        protected int $goalGoalkeeper,
        protected int $goalDefender,
        protected int $goalMidfielder,
        protected int $goalForward,
        protected int $assistGoalkeeper,
        protected int $assistDefender,
        protected int $assistMidfielder,
        protected int $assistForward,
        protected int $goalPenalty,
        protected int $goalOwn,
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

    public function getGoalGoalkeeper(): int
    {
        return $this->goalGoalkeeper;
    }

    public function getGoalDefender(): int
    {
        return $this->goalDefender;
    }

    public function getGoalMidfielder(): int
    {
        return $this->goalMidfielder;
    }

    public function getGoalForward(): int
    {
        return $this->goalForward;
    }

    public function getAssistGoalkeeper(): int
    {
        return $this->assistGoalkeeper;
    }

    /**
     * @return int
     */
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

    public function getGoalPenalty(): int
    {
        return $this->goalPenalty;
    }

    public function getGoalOwn(): int
    {
        return $this->goalOwn;
    }

    public function getCleanSheetGoalkeeper(): int
    {
        return $this->cleanSheetGoalkeeper;
    }

    public function getCleanSheetDefender(): int
    {
        return $this->cleanSheetDefender;
    }

    public function getSpottySheetGoalkeeper(): int
    {
        return $this->spottySheetGoalkeeper;
    }

    public function getSpottySheetDefender(): int
    {
        return $this->spottySheetDefender;
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
