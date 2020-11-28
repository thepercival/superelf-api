<?php

namespace SuperElf\Period;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Competition;
use SuperElf\GameRound;
use League\Period\Period as BasePeriod;
use SuperElf\Period as PeriodBase;

class View extends PeriodBase {

    /**
     * @var ArrayCollection | GameRound[]
     */
    protected $gameRounds;

    public function __construct(Competition $competition, BasePeriod $period )
    {
        parent::__construct( $competition, $period );

        $this->gameRounds = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|GameRound[]
     */
    public function getGameRounds()
    {
        return $this->gameRounds;
    }
}