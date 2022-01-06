<?php

declare(strict_types=1);

namespace SuperElf\Period\Transfer;

use SportsHelpers\Identifiable;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Player as S11Player;
use SuperElf\Pool\User as PoolUser;

class Action extends Identifiable
{
    protected bool $outHasTeam = true;

    public function __construct(
        protected PoolUser $poolUser,
        protected TransferPeriod $transferPeriod,
        protected S11Player $playerOut
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

    public function getPlayerOut(): S11Player
    {
        return $this->playerOut;
    }

    public function outHasTeam(): bool
    {
        return $this->outHasTeam;
    }
}
