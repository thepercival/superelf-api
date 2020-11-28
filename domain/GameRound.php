<?php

namespace SuperElf;

use SuperElf\Period\View as ViewPeriod;

class GameRound {

    protected int $id;
    protected ViewPeriod $viewPeriod;
    protected int $number;

    public function __construct(ViewPeriod $viewPeriod, int $number)
    {
        $this->viewPeriod = $viewPeriod;
        $this->number = $number;
    }

    public function getViewPeriod(): ViewPeriod {
        return $this->viewPeriod;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}