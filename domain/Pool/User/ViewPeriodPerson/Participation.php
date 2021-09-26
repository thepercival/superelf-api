<?php

declare(strict_types=1);

namespace SuperElf\Pool\User\ViewPeriodPerson;

use SportsHelpers\Identifiable;
use SuperElf\GameRound as BaseGameRound;
use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;
use SuperElf\GameRound;

class Participation extends Identifiable {
    public function __construct(
        protected PoolUserViewPeriodPerson $poolUserViewPeriodPerson,
        protected GameRound $gameRound )
    {
    }

    public function getPoolUserViewPeriodPerson(): PoolUserViewPeriodPerson {
        return $this->poolUserViewPeriodPerson;
    }

    public function getGameRound(): BaseGameRound {
        return $this->gameRound;
    }

    public function getGameRoundNumber(): int {
        return $this->gameRound->getNumber();
    }
}