<?php

declare(strict_types=1);

namespace SuperElf\Game;

use DateTimeImmutable;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Team\Player;
use SportsHelpers\Identifiable;

final class MissingPlayer extends Identifiable
{
    public function __construct(
        protected AgainstGamePlace $againstGamePlace,
        protected Player $player,
        protected string $type,
        protected int $reason,
        protected string $description,
        protected int $externalType,
        protected DateTimeImmutable|null $expectedEndDate
    ) {}

    public function getAgainstGamePlace(): AgainstGamePlace
    {
        return $this->againstGamePlace;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getReason(): int
    {
        return $this->reason;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getExternalType(): int
    {
        return $this->externalType;
    }

    public function getExpectedEndDate(): DateTimeImmutable|null
    {
        return $this->expectedEndDate;
    }

    public function getAgainstSide(): string
    {
        return $this->againstGamePlace->getSide()->value;
    }
}
