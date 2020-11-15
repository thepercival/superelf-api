<?php

namespace SuperElf\Pool\Period\View;

use SuperElf\Pool\Period\View as PoolViewPeriod;

class Round {

    protected int $id;
    protected PoolViewPeriod $poolViewPeriod;
    protected int $number;

    public function __construct(PoolViewPeriod $poolViewPeriod, int $number)
    {
        $this->poolViewPeriod = $poolViewPeriod;
        $this->number = $number;
    }

    public function getPoolViewPeriod(): PoolViewPeriod {
        return $this->poolViewPeriod;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}