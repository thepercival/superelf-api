<?php
declare(strict_types=1);

namespace SuperElf\Formation;

use SportsHelpers\Identifiable;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Player as S11Player;

class Place extends Identifiable
{
    protected int $number;
    protected int $penaltyPoints = 0;

    public function __construct(
        protected FormationLine $formationLine,
        protected S11player|null $player,
        int|null $number = null,
    ) {
        if (!$this->formationLine->getPlaces()->contains($this)) {
            $this->formationLine->getPlaces()->add($this) ;
        }
        if ($number === null) {
            $number = $this->formationLine->getPlaces()->count() - 1; /* substitute */
        }
        $this->number = $number;
    }

    public function getFormationLine(): FormationLine
    {
        return $this->formationLine;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPenaltyPoints(): int
    {
        return $this->penaltyPoints;
    }

    public function setPenaltyPoints(int $penaltyPoints): void
    {
        $this->penaltyPoints = $penaltyPoints;
    }

    public function getPlayer(): S11Player|null
    {
        return $this->player;
    }

    public function setPlayer(S11Player|null $player): void
    {
        $this->player = $player;
    }
}
