<?php

namespace SuperElf\Pool\Period;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Pool;
use League\Period\Period as BasePeriod;
use SuperElf\Pool\Period as PoolPeriod;
use SuperElf\Pool\Period\View as ViewPoolPeriod;

class Assemble extends PoolPeriod {

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
}