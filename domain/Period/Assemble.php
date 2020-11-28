<?php

namespace SuperElf\Period;

use League\Period\Period as BasePeriod;
use Sports\Competition;
use SuperElf\Period as S11Period;
use SuperElf\Period\View as ViewPeriod;

class Assemble extends S11Period {

    protected ViewPeriod $viewPeriod;

    public function __construct(Competition $competition, BasePeriod $period, ViewPeriod $viewPeriod)
    {
        parent::__construct( $competition, $period );
        $this->viewPeriod = $viewPeriod;
    }

    public function getViewPeriod(): ViewPeriod
    {
        return $this->viewPeriod;
    }
}