<?php

declare(strict_types=1);

namespace SuperElf\Pool\User\ViewPeriodPerson;

use SuperElf\GameRound as BaseGameRound;
use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;
use SuperElf\GameRound;

class Participation {
    protected int $id;
    protected BaseGameRound $gameRound;
    protected PoolUserViewPeriodPerson $poolUserViewPeriodPerson;

    public function __construct(PoolUserViewPeriodPerson $poolUserViewPeriodPerson, GameRound $gameRound )
    {
        $this->gameRound = $gameRound;
        $this->setPoolUserViewPeriodPerson( $poolUserViewPeriodPerson );
    }

    public function getPoolUserViewPeriodPerson(): PoolUserViewPeriodPerson {
        return $this->poolUserViewPeriodPerson;
    }

    protected function setPoolUserViewPeriodPerson(PoolUserViewPeriodPerson $poolUserViewPeriodPerson)
    {
        if (!$poolUserViewPeriodPerson->getParticipations()->contains($this)) {
            $poolUserViewPeriodPerson->getParticipations()->add($this) ;
        }
        $this->poolUserViewPeriodPerson = $poolUserViewPeriodPerson;
    }

    public function getGameRound(): BaseGameRound {
        return $this->gameRound;
    }

    public function getGameRoundNumber(): int {
        return $this->gameRound->getNumber();
    }
}