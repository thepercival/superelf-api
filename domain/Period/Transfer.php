<?php

namespace SuperElf\Period;

use League\Period\Period as BasePeriod;
use Sports\Competition;
use SuperElf\Period as S11Period;
use SuperElf\Period\View as ViewPeriod;

class Transfer extends S11Period {

    protected int $maxNrOfTransfers;
    protected ViewPeriod $viewPeriod;

    public function __construct(Competition $competition, BasePeriod $period, ViewPeriod $viewPeriod, int $maxNrOfTransfers )
    {
        parent::__construct( $competition, $period );
        $this->viewPeriod = $viewPeriod;
        $this->maxNrOfTransfers = $maxNrOfTransfers;
    }

    public function getViewPeriod(): ViewPeriod
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