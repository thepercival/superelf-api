<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State;
use SportsHelpers\Identifiable;
use SuperElf\Periods\ViewPeriod as ViewPeriod;

class GameRound extends Identifiable
{
    public function __construct(protected ViewPeriod $viewPeriod, protected int $number)
    {
        if (!$viewPeriod->getGameRounds()->contains($this)) {
            $viewPeriod->getGameRounds()->add($this) ;
        }
    }

    public function getViewPeriod(): ViewPeriod
    {
        return $this->viewPeriod;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
