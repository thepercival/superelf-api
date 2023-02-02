<?php

declare(strict_types=1);

namespace SuperElf\Periods\TransferPeriod;

use Sports\Sport\FootballLine;
use SportsHelpers\Identifiable;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Pool\User as PoolUser;

class Action extends Identifiable
{
    // protected bool $outHasTeam = true;

    public function __construct(
        protected PoolUser $poolUser,
        protected TransferPeriod $transferPeriod,
        protected FootballLine $lineNumberOut,
        protected int $placeNumberOut,
    ) {
    }

    public function getPoolUser(): Pooluser
    {
        return $this->poolUser;
    }

    public function getTransferPeriod(): TransferPeriod
    {
        return $this->transferPeriod;
    }

    public function getLineNumberOut(): FootballLine
    {
        return $this->lineNumberOut;
    }

    public function getLineNumberOutNative(): int
    {
        return $this->lineNumberOut->value;
    }

    public function setLineNumberOutNative(int $lineNumberOutNative): void
    {
        $this->lineNumberOut = FootballLine::from($lineNumberOutNative);
    }

    public function getPlaceNumberOut(): int
    {
        return $this->placeNumberOut;
    }
}
