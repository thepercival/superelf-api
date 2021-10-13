<?php
declare(strict_types=1);

namespace SuperElf\Substitute;

use SportsHelpers\Identifiable;
use SuperElf\GameRound as BaseGameRound;
use SuperElf\Substitute;
use SuperElf\GameRound;

class Participation extends Identifiable {
    public function __construct(
        protected Substitute $substitute,
        protected GameRound $gameRound )
    {
    }

    public function getSubstitute(): Substitute {
        return $this->substitute;
    }

    public function getGameRound(): BaseGameRound {
        return $this->gameRound;
    }

    public function getGameRoundNumber(): int {
        return $this->gameRound->getNumber();
    }
}