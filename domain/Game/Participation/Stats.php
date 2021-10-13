<?php
declare(strict_types=1);

namespace SuperElf\Participation;

class Stats
{
    public function __construct(
        protected int $teamResult,
        protected int $nrOfFieldGoals,
        protected int $nrOfScoredPenalties,
        protected int $nrOfOwnGoals,
        protected int $nrOfAssists,
        protected bool $cleanSheet,
        protected bool $spottySheet,
        protected int $minuteYellowCard1,
        protected int $minuteYellowCard2,
        protected int $minuteRedCard,
        protected int $minuteOut,
        protected int $minuteIn
    ) {
    }

    public function getTeamResult(): int
    {
        return $this->teamResult;
    }

    public function getNrOfFieldGoals(): int
    {
        return $this->nrOfFieldGoals;
    }

    public function getNrOfScoredPenalties(): int
    {
        return $this->nrOfScoredPenalties;
    }

    public function getNrOfOwnGoals(): int
    {
        return $this->nrOfOwnGoals;
    }

    public function getNrOfAssists(): int
    {
        return $this->nrOfAssists;
    }

    public function hasCleanSheet(): bool
    {
        return $this->cleanSheet;
    }

    public function hasSpottySheet(): bool
    {
        return $this->spottySheet;
    }

    public function getMinuteYellowCard1(): int
    {
        return $this->minuteYellowCard1;
    }

    public function getMinuteYellowCard2(): int
    {
        return $this->minuteYellowCard2;
    }

    public function getMinuteRedCard(): int
    {
        return $this->minuteRedCard;
    }

    public function isInStartingLineup(): bool
    {
        return $this->minuteIn === 0;
    }

    public function getMinuteOut(): int
    {
        return $this->minuteOut;
    }

    public function getMinuteIn(): int
    {
        return $this->minuteIn;
    }

    public function inStartingLineup(): bool
    {
        return $this->minuteIn === 0;
    }

    public function substituted(): bool
    {
        return $this->minuteOut > 0;
    }
}
