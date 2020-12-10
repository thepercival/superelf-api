<?php

declare(strict_types=1);

namespace SuperElf\Season\ScoreUnit;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Game;
use SuperElf\Period\View\Person;
use SuperElf\ScoreUnit;
use Sports\Sport\Custom as SportCustom;
use SuperElf\Season\ScoreUnit as SeasonScoreUnit;

class Calculator
{
    /**
     * @var array
     */
    private array $calculators = [];

    public function __construct()
    {
        $this->initCalculators();
    }

    /**
     * @param array $stats
     * @param array|SeasonScoreUnit[] $seasonScoreUnits
     * @return array|int[]
     */
    public function getPoints( array $stats, array $seasonScoreUnits ): array
    {
        $points = [];
        foreach ($seasonScoreUnits as $seasonScoreUnit) {
            $points[$seasonScoreUnit->getNumber()] = $this->calculators[$seasonScoreUnit->getNumber()]($seasonScoreUnit, $stats);
        }
        return $points;
    }

    protected function initCalculators() {
        $this->calculators[ScoreUnit::POINTS_WIN] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ): int {
            return $stats[Person::RESULT] === Game::RESULT_WIN ? $seasonScoreUnit->getPoints() : 0;
        };
        $this->calculators[ScoreUnit::POINTS_DRAW] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ): int {
            return $stats[Person::RESULT] === Game::RESULT_DRAW ? $seasonScoreUnit->getPoints() : 0;
        };
        $fieldGoalsAssistsCalculator = function( SeasonScoreUnit $seasonScoreUnit, array $stats, int $line, int $stat ): int {
            if( $stats[Person::LINE] !== $line ) {
                return 0;
            }
            return $stats[$stat] * $seasonScoreUnit->getPoints();
        };
        $this->calculators[ScoreUnit::GOAL_GOALKEEPER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($fieldGoalsAssistsCalculator): int {
            return $fieldGoalsAssistsCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_GoalKepeer, Person::GOALS_FIELD );
        };
        $this->calculators[ScoreUnit::GOAL_DEFENDER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($fieldGoalsAssistsCalculator): int {
            return $fieldGoalsAssistsCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_Defense, Person::GOALS_FIELD );
        };
        $this->calculators[ScoreUnit::GOAL_MIDFIELDER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($fieldGoalsAssistsCalculator): int {
            return $fieldGoalsAssistsCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_Midfield, Person::GOALS_FIELD );
        };
        $this->calculators[ScoreUnit::GOAL_FORWARD] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($fieldGoalsAssistsCalculator): int {
            return $fieldGoalsAssistsCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_Forward, Person::GOALS_FIELD );
        };
        $this->calculators[ScoreUnit::GOAL_PENALTY] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ): int {
            return $stats[Person::GOALS_PENALTY] * $seasonScoreUnit->getPoints();
        };
        $this->calculators[ScoreUnit::GOAL_OWN] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ): int {
            return $stats[Person::GOALS_OWN] * $seasonScoreUnit->getPoints();
        };
        $this->calculators[ScoreUnit::ASSIST_GOALKEEPER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($fieldGoalsAssistsCalculator): int {
            return $fieldGoalsAssistsCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_GoalKepeer, Person::ASSISTS );
        };
        $this->calculators[ScoreUnit::ASSIST_DEFENDER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($fieldGoalsAssistsCalculator): int {
            return $fieldGoalsAssistsCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_Defense, Person::ASSISTS );
        };
        $this->calculators[ScoreUnit::ASSIST_MIDFIELDER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($fieldGoalsAssistsCalculator): int {
            return $fieldGoalsAssistsCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_Midfield, Person::ASSISTS );
        };
        $this->calculators[ScoreUnit::ASSIST_FORWARD] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($fieldGoalsAssistsCalculator): int {
            return $fieldGoalsAssistsCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_Forward, Person::ASSISTS );
        };
        $sheetCalculator = function( SeasonScoreUnit $seasonScoreUnit, array $stats, int $line, int $stat ): int {
            return ( $stats[Person::LINE] === $line && $stats[$stat]) ? $seasonScoreUnit->getPoints() : 0;
        };
        $this->calculators[ScoreUnit::SHEET_CLEAN_GOALKEEPER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($sheetCalculator): int {
            return $sheetCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_GoalKepeer, Person::SHEET_CLEAN );
        };
        $this->calculators[ScoreUnit::SHEET_CLEAN_DEFENDER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($sheetCalculator): int {
            return $sheetCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_Defense, Person::SHEET_CLEAN );
        };
        $this->calculators[ScoreUnit::SHEET_SPOTTY_GOALKEEPER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($sheetCalculator): int {
            return $sheetCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_GoalKepeer, Person::SHEET_SPOTTY );
        };
        $this->calculators[ScoreUnit::SHEET_SPOTTY_DEFENDER] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ) use ($sheetCalculator): int {
            return $sheetCalculator( $seasonScoreUnit, $stats, SportCustom::Football_Line_Defense, Person::SHEET_SPOTTY );
        };
        $this->calculators[ScoreUnit::CARD_YELLOW] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ): int {
            return $stats[Person::CARDS_YELLOW] * $seasonScoreUnit->getPoints();
        };
        $this->calculators[ScoreUnit::CARD_RED] = function( SeasonScoreUnit $seasonScoreUnit, array $stats ): int {
            return $stats[Person::CARD_RED] * $seasonScoreUnit->getPoints();
        };
    }
}
