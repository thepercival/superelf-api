<?php

namespace SuperElf\Pool\Period;

use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Formation;
use SuperElf\Pool;
use League\Period\Period as BasePeriod;
use SuperElf\Pool\Period as PeriodBase;

class View extends PeriodBase {

    /**
     * @var ArrayCollection | View\Round[]
     */
    protected $rounds;

    public function __construct(BasePeriod $period )
    {
        parent::__construct( $period );

        $this->rounds = new ArrayCollection();
    }

    public function getRounds()
    {
        return $this->rounds;
    }
}