<?php
declare(strict_types=1);

namespace SuperElf\Season\ScoreUnit;

use Sports\Sport;
use SportsHelpers\Against\Result as AgainstResult;
use Sports\Game;
use SuperElf\Points;
use Sports\Sport\Custom as SportCustom;

class Calculator
{
    public function __construct()
    {
    }

    public function getPoints(Game\Participation $gameParticipation, Points $points): int
    {
        $total = $this->getResultPoints($gameParticipation, $points);
        $total += $this->getGoalPoints($gameParticipation, $points);
        $total += $this->getAssistPoints($gameParticipation, $points);
        $total += $this->getCleanSheetPoints($gameParticipation, $points);
        $total += $this->getSpottySheetPoints($gameParticipation, $points);
        $total += $this->getCardPoints($gameParticipation, $points);
        return $total;
    }

    protected function getResultPoints(Game\Participation $gameParticipation, Points $points): int
    {
        $result = $gameParticipation->getResult();
        if ($result === AgainstResult::WIN) {
            return  $points->getResultWin();
        } elseif ($result === AgainstResult::DRAW) {
            return  $points->getResultDraw();
        }
        return 0;
    }

    protected function getGoalPoints(Game\Participation $gameParticipation, Points $points): int
    {
        $total = 0;
        $line = $gameParticipation->getPlayer()->getLine();
        foreach ($gameParticipation->getGoals() as $goal) {
            if ($goal->getPenalty()) {
                $total +=  $points->getGoalPenalty();
            } elseif ($goal->getOwn()) {
                $total +=  $points->getGoalOwn();
            } else {
                if ($line === SportCustom::Football_Line_GoalKepeer) {
                    $total +=  $points->getGoalGoalKeeper();
                } elseif ($line === SportCustom::Football_Line_Defense) {
                    $total +=  $points->getGoalDefender();
                } elseif ($line === SportCustom::Football_Line_Midfield) {
                    $total +=  $points->getGoalMidfielder();
                } else {
                    $total += $points->getGoalForward();
                }
            }
        }
        return $total;
    }

    protected function getAssistPoints(Game\Participation $gameParticipation, Points $points): int
    {
        $total = 0;
        $line = $gameParticipation->getPlayer()->getLine();
        foreach ($gameParticipation->getAssists() as $assist) {
            if ($line === SportCustom::Football_Line_GoalKepeer) {
                $total +=  $points->getAssistGoalKeeper();
            } elseif ($line === SportCustom::Football_Line_Defense) {
                $total +=  $points->getAssistDefender();
            } elseif ($line === SportCustom::Football_Line_Midfield) {
                $total +=  $points->getAssistMidfielder();
            } else {
                $total += $points->getAssistForward();
            }
        }
        return $total;
    }

    protected function getCleanSheetPoints(Game\Participation $gameParticipation, Points $points): int
    {
        $line = $gameParticipation->getPlayer()->getLine();
        if ($line !== SportCustom::Football_Line_GoalKepeer && $line !== SportCustom::Football_Line_Defense) {
            return 0;
        }
        $goalsConceived = $gameParticipation->getGoalsConceived();
        if ($goalsConceived > 0) {
            return 0;
        }
        if ($line === SportCustom::Football_Line_GoalKepeer) {
            return $points->getCleanSheetGoalKeeper();
        }
        return $points->getCleanSheetDefender();
    }

    protected function getSpottySheetPoints(Game\Participation $gameParticipation, Points $points): int
    {
        $line = $gameParticipation->getPlayer()->getLine();
        if ($line !== SportCustom::Football_Line_GoalKepeer && $line !== SportCustom::Football_Line_Defense) {
            return 0;
        }
        $goalsConceived = $gameParticipation->getGoalsConceived();
        if ($goalsConceived > 0) {
            return 0;
        }
        if ($line === SportCustom::Football_Line_GoalKepeer) {
            return $points->getSpottySheetGoalKeeper();
        }
        return $points->getSpottySheetDefender();
    }

    protected function getCardPoints(Game\Participation $gameParticipation, Points $points): int
    {
        $total = 0;
        foreach ($gameParticipation->getCards() as $cardEvent) {
            if ($cardEvent->getType() === Sport::WARNING) {
                $total += $points->getCardYellow();
            }
            if ($cardEvent->getType() === Sport::SENDOFF && $gameParticipation->getCards()->count() < 3) {
                $total += $points->getCardRed();
            }
        }
        return $total;
    }
}
