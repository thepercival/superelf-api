<?php

declare(strict_types=1);

namespace SuperElf\Substitute;

use SportsHelpers\Identifiable;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\GameRound;
use SuperElf\GameRound as BaseGameRound;

class Appearance extends Identifiable
{
    public function __construct(
        protected FormationLine $formationLine,
        protected GameRound $gameRound
    ) {
    }

    public function getFormationLine(): FormationLine
    {
        return $this->formationLine;
    }

    public function getGameRound(): BaseGameRound
    {
        return $this->gameRound;
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRound->getNumber();
    }
}
