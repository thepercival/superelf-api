<?php
declare(strict_types=1);

namespace SuperElf;

use SportsHelpers\Identifiable;
use SuperElf\Player as S11Player;

class Statistics extends Identifiable
{
    public function __construct(
        protected S11Player $player,
        protected GameRound $gameRound,
        protected int $result,
        protected int $beginMinute,
        protected int $endMinute,
        protected int $nrOfFieldGoals,
        protected int $nrOfAssists,
        protected int $nrOfPenalties,
        protected int $nrOfOwnGoals,
        protected int $sheet,
        protected int $nrOfYellowCards,
        protected bool $directRedCard,
        protected int $playerLine,
        protected \DateTimeImmutable $gameStartDateTime,
        protected \DateTimeImmutable $updatedAt
    ) {
    }

    public function getGameRound(): GameRound
    {
        return $this->gameRound;
    }

    public function getResult(): int
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

    public function isStarting(): bool
    {
        return $this->beginMinute === 0;
    }

    public function getEndMinute(): int
    {
        return $this->endMinute;
    }

    public function setEndMinute(int $minute): void
    {
        $this->endMinute = $minute;
    }

    public function isSubstituted(): bool
    {
        return $this->endMinute > 0;
    }

    public function hasAppeared(): bool
    {
        return $this->beginMinute > -1;
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

    public function getPlayerLine(): int
    {
        return $this->playerLine;
    }
}
