<?php

declare(strict_types=1);

namespace SuperElf;

use SportsHelpers\Identifiable;
use SuperElf\Period\View as ViewPeriod;

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
