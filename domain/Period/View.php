<?php
declare(strict_types=1);

namespace SuperElf\Period;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Sports\Competition;
use SuperElf\GameRound;
use League\Period\Period as BasePeriod;
use SuperElf\Period as PeriodBase;
use SuperElf\Pool;

class View extends PeriodBase {

    /**
     * @var ArrayCollection<int|string, GameRound>|PersistentCollection<int|string, GameRound>
     */
    protected ArrayCollection|PersistentCollection $gameRounds;

    public function __construct(Competition $competition, BasePeriod $period )
    {
        parent::__construct( $competition, $period );

        $this->gameRounds = new ArrayCollection();
    }

    public function getGameRounds(): ArrayCollection|PersistentCollection
    {
        return $this->gameRounds;
    }
}