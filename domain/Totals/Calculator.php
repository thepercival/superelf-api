<?php

namespace SuperElf\Totals;

use SportsHelpers\Against\Result;
use SuperElf\CompetitionConfig;
use SuperElf\Formation;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Player as S11Player;
use SuperElf\Points;
use SuperElf\Statistics;
use SuperElf\Totals;

class Calculator
{
    public function __construct(protected CompetitionConfig $competitionConfig)
    {
    }

    public function getTotals(Formation $formation): Totals {
        $totals = new Totals();
        foreach( $formation->getLines() as $formationLine ) {
            foreach( $formationLine->getPlaces() as $formationPlace) {
                $totals = $totals->add($formationPlace->getTotals());
            }
        }
        return $totals;
    }



    /**
     * @param Totals $totals
     * @param list<Statistics> $stats
     *
     */
    public function updateTotals(Totals $totals, array $stats): void
    {
        $totals->reset();
        foreach ($stats as $statistics) {
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

    public function updateTotalPoints(FormationPlace|S11Player $totalsCarier, Points $points): void
    {
        $totalsCarier->setTotalPoints(
            $totalsCarier->getTotals()->getPoints($totalsCarier->getLine(), $points, null)
        );
    }
}