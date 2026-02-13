<?php

declare(strict_types=1);

namespace SuperElf\Periods;

use Sports\Sport\FootballLine;
use SportsHelpers\Identifiable;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Pool\User as PoolUser;

class TransferPeriodAction extends Identifiable
{
    protected \DateTimeImmutable $createdDateTime;


    public function __construct(
        protected PoolUser $poolUser,
        protected TransferPeriod $transferPeriod,
        protected FootballLine $lineNumberOut,
        protected int $placeNumberOut
    ) {
        $this->createdDateTime = new \DateTimeImmutable();
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

    public function getCreatedDateTime(): \DateTimeImmutable {
        return $this->createdDateTime;
    }
}
