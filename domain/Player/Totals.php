<?php

declare(strict_types=1);

namespace SuperElf\Player;

use Sports\Sport\FootballLine;
use SportsHelpers\Identifiable;
use SuperElf\Points;

class Totals extends Identifiable
{
    public function __construct(
        protected int $nrOfWins = 0,
        protected int $nrOfDraws = 0,
        protected int $nrOfTimesStarted = 0,
        protected int $nrOfTimesSubstituted = 0,
        protected int $nrOfTimesSubstitute = 0,
        protected int $nrOfTimesNotAppeared = 0,
        protected int $nrOfFieldGoals = 0,
        protected int $nrOfAssists = 0,
        protected int $nrOfPenalties = 0,
        protected int $nrOfOwnGoals = 0,
        protected int $nrOfCleanSheets = 0,
        protected int $nrOfSpottySheets = 0,
        protected int $nrOfYellowCards = 0,
        protected int $nrOfRedCards = 0,
        protected \DateTimeImmutable|null $updatedAt = null
    ) {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reset(): void
    {
        $this->nrOfWins = 0;
        $this->nrOfDraws = 0;
        $this->nrOfTimesStarted = 0;
        $this->nrOfTimesSubstituted = 0;
        $this->nrOfTimesSubstitute = 0;
        $this->nrOfTimesNotAppeared = 0;
        $this->nrOfFieldGoals = 0;
        $this->nrOfAssists = 0;
        $this->nrOfPenalties = 0;
        $this->nrOfOwnGoals = 0;
        $this->nrOfCleanSheets = 0;
        $this->nrOfSpottySheets = 0;
        $this->nrOfYellowCards = 0;
        $this->nrOfRedCards = 0;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getNrOfWins(): int
    {
        return $this->nrOfWins;
    }

    public function incrementNrOfWins(): void
    {
        $this->nrOfWins++;
    }

    public function getNrOfDraws(): int
    {
        return $this->nrOfWins;
    }

    public function incrementNrOfDraws(): void
    {
        $this->nrOfDraws++;
    }

    public function getNrOfTimesStarted(): int
    {
        return $this->nrOfTimesStarted;
    }

    public function incrementNrOfTimesStarted(): void
    {
        $this->nrOfTimesStarted++;
    }

    public function getNrOfTimesSubstituted(): int
    {
        return $this->nrOfTimesSubstituted;
    }

    public function incrementNrOfTimesSubstituted(): void
    {
        $this->nrOfTimesSubstituted++;
    }

    public function getNrOfTimesSubstitute(): int
    {
        return $this->nrOfTimesSubstitute;
    }

    public function incrementNrOfTimesSubstitute(): void
    {
        $this->nrOfTimesSubstitute++;
    }

    public function getNrOfTimesNotAppeared(): int
    {
        return $this->nrOfTimesNotAppeared;
    }

    public function incrementNrOfTimesNotAppeared(): void
    {
        $this->nrOfTimesNotAppeared++;
    }

    public function getNrOfFieldGoals(): int
    {
        return $this->nrOfFieldGoals;
    }

    public function addNrOfFieldGoals(int $nrOfFieldGoals): void
    {
        $this->nrOfFieldGoals += $nrOfFieldGoals;
    }

    public function getNrOfAssists(): int
    {
        return $this->nrOfAssists;
    }

    public function addNrOfAssists(int $nrOfAssists): void
    {
        $this->nrOfAssists += $nrOfAssists;
    }

    public function getNrOfPenalties(): int
    {
        return $this->nrOfPenalties;
    }

    public function addNrOfPenalties(int $nrOfPenalties): void
    {
        $this->nrOfPenalties += $nrOfPenalties;
    }

    public function getNrOfOwnGoals(): int
    {
        return $this->nrOfOwnGoals;
    }

    public function addNrOfOwnGoals(int $nrOfOwnGoals): void
    {
        $this->nrOfOwnGoals += $nrOfOwnGoals;
    }

    public function getNrOfCleanSheets(): int
    {
        return $this->nrOfCleanSheets;
    }

    public function incrementNrOfCleanSheets(): void
    {
        $this->nrOfCleanSheets++;
    }

    public function getNrOfSpottySheets(): int
    {
        return $this->nrOfSpottySheets;
    }

    public function incrementNrOfSpottySheets(): void
    {
        $this->nrOfSpottySheets++;
    }

    public function getNrOfYellowCards(): int
    {
        return $this->nrOfYellowCards;
    }

    public function addNrOfYellowCards(int $nrOfYellowCards): void
    {
        $this->nrOfYellowCards += $nrOfYellowCards;
    }

    public function getNrOfRedCards(): int
    {
        return $this->nrOfRedCards;
    }

    public function incrementNrOfRedCards(): void
    {
        $this->nrOfRedCards++;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getPoints(FootballLine $line, Points $points): int
    {
        return $this->getNrOfWins() * $points->getResultWin()
            + $this->getNrOfDraws() * $points->getResultDraw()
            + $this->getNrOfFieldGoals() * $points->getFieldGoal($line)
            + $this->getNrOfAssists() * $points->getAssist($line)
            + $this->getNrOfPenalties() * $points->getPenalty()
            + $this->getNrOfOwnGoals() * $points->getOwnGoal()
            + $this->getNrOfCleanSheets() * $points->getCleanSheet($line)
            + $this->getNrOfSpottySheets() * $points->getSpottySheet($line)
            + $this->getNrOfYellowCards() * $points->getCardYellow()
            + $this->getNrOfRedCards() * $points->getCardRed();
    }
}
