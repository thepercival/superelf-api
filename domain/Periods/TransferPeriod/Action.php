<?php

declare(strict_types=1);

namespace SuperElf\Periods\TransferPeriod;

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
        protected FormationPlace $formationPlace
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

    public function getFormationPlace(): FormationPlace
    {
        return $this->formationPlace;
    }

//    public function outHasTeam(): bool
//    {
//        return $this->outHasTeam;
//    }
}
