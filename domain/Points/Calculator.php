<?php
declare(strict_types=1);

namespace SuperElf\Points;

use Sports\Sport;
use SportsHelpers\Against\Result as AgainstResult;
use SuperElf\Statistics;
use SuperElf\Points;
use Sports\Sport\Custom as SportCustom;

class Calculator
{
    public function __construct()
    {
    }

    public function getPoints(Statistics $statistics, Points $points): int
    {
        $total = $this->getResultPoints($statistics, $points);
        $total += $this->getGoalPoints($statistics, $points);
        $total += $this->getAssistPoints($statistics, $points);
        $total += $this->getCleanSheetPoints($statistics, $points);
        $total += $this->getSpottySheetPoints($statistics, $points);
        $total += $this->getCardPoints($statistics, $points);
        return $total;
    }

    protected function getResultPoints(Statistics $statistics, Points $points): int
    {
        $result = $statistics->getResult();
        if ($result === AgainstResult::WIN) {
            return  $points->getResultWin();
        } elseif ($result === AgainstResult::DRAW) {
            return  $points->getResultDraw();
        }
        return 0;
    }

    protected function getGoalPoints(Statistics $statistics, Points $points): int
    {
        $total = $statistics->getNrOfFieldGoals() * $points->getFieldGoal($statistics->getPlayerLine());
        $total += $statistics->getNrOfAssists() * $points->getAssist($statistics->getPlayerLine());
        $total += $statistics->getNrOfPenalties() * $points->getPenalty();
        $total += $statistics->getNrOfOwnGoals() * $points->getOwnGoal();
        return $total;
    }


    protected function getAssistPoints(Statistics $statistics, Points $points): int
    {
        return $statistics->getNrOfAssists() * $points->getAssist($statistics->getPlayerLine());
    }

    protected function getCleanSheetPoints(Statistics $statistics, Points $points): int
    {
        return $statistics->hasCleanSheet() ? $points->getCleanSheet($statistics->getPlayerLine()) : 0;
    }

    protected function getSpottySheetPoints(Statistics $statistics, Points $points): int
    {
        return $statistics->hasSpottySheet() ? $points->getSpottySheet($statistics->getPlayerLine()) : 0;
    }

    protected function getCardPoints(Statistics $statistics, Points $points): int
    {
        $total = $statistics->getNrOfYellowCards() * $points->getCardYellow();
        $total += $statistics->directRedCard() ? $points->getCardRed() : 0;
        return $total;
    }
}
