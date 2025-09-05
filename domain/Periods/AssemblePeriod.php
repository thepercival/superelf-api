<?php

namespace SuperElf\Periods;

use League\Period\Period;
use SuperElf\Period as BasePeriod;
use SuperElf\Periods\ViewPeriod as ViewPeriod;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class AssemblePeriod extends BasePeriod
{
    protected ViewPeriod $viewPeriod;

    public function __construct(Period $period, ViewPeriod $viewPeriod)
    {
        parent::__construct($period);
        $this->viewPeriod = $viewPeriod;
    }

    public function getViewPeriod(): ViewPeriod
    {
        return $this->viewPeriod;
    }
}
