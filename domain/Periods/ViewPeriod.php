<?php

declare(strict_types=1);

namespace SuperElf\Periods;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use League\Period\Period;
use SuperElf\GameRound;
use SuperElf\Period as BasePeriod;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class ViewPeriod extends BasePeriod
{
    /**
     * @var Collection<int|string, GameRound>
     */
    protected Collection $gameRounds;

    public function __construct(Period $period)
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
