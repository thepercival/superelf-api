<?php

namespace SuperElf\Period;

use League\Period\Period as BasePeriod;
use SuperElf\Period as S11Period;
use SuperElf\Period\View as ViewPeriod;

class Assemble extends S11Period
{
    protected ViewPeriod $viewPeriod;

    public function __construct(BasePeriod $period, ViewPeriod $viewPeriod)
    {
        parent::__construct($period);
        $this->viewPeriod = $viewPeriod;
    }

    public function getViewPeriod(): ViewPeriod
    {
        return $this->viewPeriod;
    }
}
