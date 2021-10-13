<?php
declare(strict_types=1);
namespace SuperElf;

use SportsHelpers\Identifiable;
use SuperElf\Period\View as ViewPeriod;

class GameRound extends Identifiable
{
    protected ViewPeriod $viewPeriod;
    protected int $number;

    public function __construct(ViewPeriod $viewPeriod, int $number)
    {
        $this->viewPeriod = $viewPeriod;
        if (!$viewPeriod->getGameRounds()->contains($this)) {
            $viewPeriod->getGameRounds()->add($this) ;
        }
        $this->number = $number;
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
