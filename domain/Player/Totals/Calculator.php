<?php

namespace SuperElf\Player\Totals;

use SportsHelpers\Against\Result;
use SuperElf\CompetitionConfig;
use SuperElf\Player as S11Player;

class Calculator
{
    public function __construct(protected CompetitionConfig $competitionConfig)
    {
    }

    public function updateTotals(S11Player $s11Player): void
    {
        $totals = $s11Player->getTotals();
        $totals->reset();
        foreach ($s11Player->getStatistics() as $statistics) {
            if ($statistics->getResult() === Result::Win) {
                $totals->incrementNrOfWins();
            }
            if ($statistics->getResult() === Result::Draw) {
                $totals->incrementNrOfDraws();
            }
            if ($statistics->isStarting()) {
                $totals->incrementNrOfTimesStarted();
            }
            if ($statistics->isSubstitute()) {
                $totals->incrementNrOfTimesSubstitute();
            }
            if ($statistics->isSubstituted()) {
                $totals->incrementNrOfTimesSubstituted();
            }
            if (!$statistics->hasAppeared()) {
                $totals->incrementNrOfTimesNotAppeared();
            }
            $totals->addNrOfFieldGoals($statistics->getNrOfFieldGoals());
            $totals->addNrOfAssists($statistics->getNrOfAssists());
            $totals->addNrOfPenalties($statistics->getNrOfPenalties());
            $totals->addNrOfOwnGoals($statistics->getNrOfOwnGoals());
            if ($statistics->hasCleanSheet()) {
                $totals->incrementNrOfCleanSheets();
            }
            if ($statistics->hasSpottySheet()) {
                $totals->incrementNrOfSpottySheets();
            }
            $totals->addNrOfYellowCards($statistics->getNrOfYellowCards());
            if ($statistics->directRedCard()) {
                $totals->incrementNrOfRedCards();
            }
        }
        $totals->setUpdatedAt(new \DateTimeImmutable());
    }

    public function updateTotalPoints(S11Player $s11Player): void
    {
        $s11Player->setTotalPoints(
            $s11Player->getTotals()->getPoints($s11Player->getLine(), $this->competitionConfig)
        );
    }
}