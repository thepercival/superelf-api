<?php

declare(strict_types=1);

namespace SuperElf\Period;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use League\Period\Period as BasePeriod;
use SuperElf\GameRound;
use SuperElf\Period as PeriodBase;

class View extends PeriodBase
{
    /**
     * @var Collection<int|string, GameRound>
     */
    protected Collection $gameRounds;

    public function __construct(BasePeriod $period)
    {
        parent::__construct($period);

        $this->gameRounds = new ArrayCollection();
    }

    /**
     * @return Collection<int|string, GameRound>
     */
    public function getGameRounds(): Collection
    {
        return $this->gameRounds;
    }

    public function getGameRound(int $gameRoundNumber): GameRound|null
    {
        $gameRounds = $this->gameRounds->filter(fn (GameRound $gameRound) => $gameRound->getNumber() === $gameRoundNumber);
        $gameRound = $gameRounds->first();
        return $gameRound === false ? null : $gameRound;
    }
}
