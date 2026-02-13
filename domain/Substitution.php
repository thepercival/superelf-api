<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Sport\FootballLine;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Periods\TransferPeriodAction;
use SuperElf\Pool\User as PoolUser;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Substitution extends TransferPeriodAction
{
    public function __construct(
        PoolUser $poolUser, TransferPeriod $transferPeriod,
        protected FootballLine $lineNumberOut,
        protected int $placeNumberOut
    ) {
        parent::__construct($poolUser, $transferPeriod, $lineNumberOut, $placeNumberOut);
    }
}
