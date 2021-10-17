<?php
declare(strict_types=1);

namespace SuperElf\Points;

use SportsHelpers\Against\Result as AgainstResult;
use SuperElf\Statistics;
use SuperElf\Points;

class Calculator
{
    public function __construct()
    {
    }

    public function getStatisticsPoints(int $line, Statistics $statistics, Points $points): int
    {
        $total = $this->getResultPoints($statistics, $points);
        $total += $this->getGoalPoints($line, $statistics, $points);
        $total += $this->getAssistPoints($line, $statistics, $points);
        $total += $this->getCleanSheetPoints($line, $statistics, $points);
        $total += $this->getSpottySheetPoints($line, $statistics, $points);
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

    protected function getGoalPoints(int $line, Statistics $statistics, Points $points): int
    {
        $total = $statistics->getNrOfFieldGoals() * $points->getFieldGoal($line);
        $total += $statistics->getNrOfAssists() * $points->getAssist($line);
        $total += $statistics->getNrOfPenalties() * $points->getPenalty();
        $total += $statistics->getNrOfOwnGoals() * $points->getOwnGoal();
        return $total;
    }


    protected function getAssistPoints(int $line, Statistics $statistics, Points $points): int
    {
        return $statistics->getNrOfAssists() * $points->getAssist($line);
    }

    protected function getCleanSheetPoints(int $line, Statistics $statistics, Points $points): int
    {
        return $statistics->hasCleanSheet() ? $points->getCleanSheet($line) : 0;
    }

    protected function getSpottySheetPoints(int $line, Statistics $statistics, Points $points): int
    {
        return $statistics->hasSpottySheet() ? $points->getSpottySheet($line) : 0;
    }

    protected function getCardPoints(Statistics $statistics, Points $points): int
    {
        $total = $statistics->getNrOfYellowCards() * $points->getCardYellow();
        $total += $statistics->directRedCard() ? $points->getCardRed() : 0;
        return $total;
    }
}
