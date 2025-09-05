<?php

namespace SuperElf\Totals;

use Exception;
use SportsHelpers\Against\Result;
use SuperElf\CompetitionConfig;
use SuperElf\Formation;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\GameRound\TotalsCalculator;
use SuperElf\Player as S11Player;
use SuperElf\Points;
use SuperElf\Statistics;
use SuperElf\Totals;

/**
 * @psalm-api
 */
final class Calculator
{
    public function __construct()
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
            if ($statistics->hasStarted()) {
                $totals->incrementNrOfTimesStarted();
            }
            if ($statistics->hasBeenSubstitute()) {
                $totals->incrementNrOfTimesSubstitute();
            }
            if ($statistics->hasBeenSubstituted()) {
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
        try {
            if( $totalsCarier instanceof S11Player) {
                $line = $totalsCarier->getLineFromPlayers();
            } else {
                $line = $totalsCarier->getLine();
            }
            $totalPoints = $totalsCarier->getTotals()->getPoints($line, $points, null);
            $totalsCarier->setTotalPoints($totalPoints);
        } catch(Exception $e ) {
            // $er = $e;
        }
    }
}