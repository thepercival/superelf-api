<?php

declare(strict_types=1);

namespace SuperElf;

use SportsHelpers\Identifiable;
use SuperElf\Player as S11Player;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class ScoutedPlayer extends Identifiable
{
    public function __construct(
        protected User $user,
        protected S11Player $s11Player,
        protected int $nrOfStars
    )
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getS11Player(): S11Player
    {
        return $this->s11Player;
    }

    public function getNrOfStars(): int
    {
        return $this->nrOfStars;
    }

    public function setNrOfStars(int $nrOfStars): void
    {
        $this->nrOfStars = $nrOfStars;
    }
}
