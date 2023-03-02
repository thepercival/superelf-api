<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Person;
use SportsHelpers\Against\Result;
use SportsHelpers\Identifiable;
use SuperElf\Player as S11Player;

class Statistics extends Identifiable
{
    public function __construct(
        protected S11Player $player,
        protected GameRound $gameRound,
        protected Result $result,
        protected int $beginMinute,
        protected int $endMinute,
        protected int $nrOfFieldGoals,
        protected int $nrOfAssists,
        protected int $nrOfPenalties,
        protected int $nrOfOwnGoals,
        protected int $sheet,
        protected int $nrOfYellowCards,
        protected bool $directRedCard,
        protected \DateTimeImmutable $gameStartDateTime,
        protected \DateTimeImmutable $updatedAt
    ) {
        if (!$this->player->getStatistics()->contains($this)) {
            $this->player->getStatistics()->add($this) ;
        }
    }

    public function getGameRound(): GameRound
    {
        return $this->gameRound;
    }

    public function getPerson(): Person
    {
        return $this->player->getPerson();
    }


    public function getResult(): Result
    {
        return $this->result;
    }

    public function getBeginMinute(): int
    {
        return $this->beginMinute;
    }

    public function setBeginMinute(int $minute): void
    {
        $this->beginMinute = $minute;
    }

    /**
     * endMinute can not be 0, always -1 or >0
     * @return int
     */
    public function getEndMinute(): int
    {
        return $this->endMinute;
    }

    public function setEndMinute(int $minute): void
    {
        $this->endMinute = $minute;
    }

    public function isStarting(): bool
    {
        return $this->beginMinute === 0;
    }

    public function isSubstitute(): bool
    {
        return $this->beginMinute > 0;
    }

    public function hasAppeared(): bool
    {
        return $this->isStarting() || $this->isSubstitute();
    }

    public function isSubstituted(): bool
    {
        return $this->endMinute > 0;
    }

    public function getNrOfFieldGoals(): int
    {
        return $this->nrOfFieldGoals;
    }

    public function getNrOfAssists(): int
    {
        return $this->nrOfAssists;
    }

    public function getNrOfPenalties(): int
    {
        return $this->nrOfPenalties;
    }

    public function getNrOfOwnGoals(): int
    {
        return $this->nrOfOwnGoals;
    }

    public function hasCleanSheet(): bool
    {
        return $this->hasSheet(Sheet::CLEAN);
    }

    public function hasSpottySheet(): bool
    {
        return $this->hasSheet(Sheet::SPOTTY);
    }

    public function hasSheet(int $sheet): bool
    {
        return $sheet === $this->sheet;
    }

    public function getNrOfYellowCards(): int
    {
        return $this->nrOfYellowCards;
    }

    public function directRedCard(): bool
    {
        return $this->directRedCard;
    }

    public function equals(Statistics $compare): bool
    {
        return $this->getResult() === $compare->getResult()
            && $this->getBeginMinute() === $compare->getBeginMinute()
            && $this->getEndMinute() === $compare->getEndMinute()
            && $this->getNrOfFieldGoals() === $compare->getNrOfFieldGoals()
            && $this->getNrOfAssists() === $compare->getNrOfAssists()
            && $this->getNrOfPenalties() === $compare->getNrOfPenalties()
            && $this->getNrOfOwnGoals() === $compare->getNrOfOwnGoals()
            && $this->hasCleanSheet() === $compare->hasCleanSheet()
            && $this->hasSpottySheet() === $compare->hasSpottySheet()
            && $this->getNrOfYellowCards() === $compare->getNrOfYellowCards()
            && $this->directRedCard() === $compare->directRedCard();
    }

    public function getResultNative(): int
    {
        return $this->result->value;
    }
}
