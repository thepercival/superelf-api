<?php

namespace SuperElf\Pool\Period;

use SuperElf\Pool;
use League\Period\Period as BasePeriod;
use SuperElf\Pool\Period as PoolPeriod;
use SuperElf\Pool\Period\View as ViewPoolPeriod;

class Transfer extends PoolPeriod {

    public const DefaultMaxAmount = 2;
    protected int $maxNrOfTransfers = self::DefaultMaxAmount;
    protected ViewPoolPeriod $viewPeriod;

    public function __construct(BasePeriod $period, ViewPoolPeriod $viewPeriod)
    {
        parent::__construct( $period );
        $this->viewPeriod = $viewPeriod;
    }

    public function getViewPeriod(): ViewPoolPeriod
    {
        return $this->viewPeriod;
    }

    public function getMaxNrOfTransfers(): int
    {
        return $this->maxNrOfTransfers;
    }

    public function setMaxNrOfTransfers(int $maxNrOfTransfers)
    {
        $this->maxNrOfTransfers = $maxNrOfTransfers;
    }
}