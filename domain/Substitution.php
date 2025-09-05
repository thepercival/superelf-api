<?php

declare(strict_types=1);

namespace SuperElf;

use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Periods\TransferPeriod\Action;
use SuperElf\Pool\User as PoolUser;
use Sports\Sport\FootballLine;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Substitution extends Action
{
    public function __construct(
        PoolUser $poolUser, TransferPeriod $transferPeriod,
        protected FootballLine $lineNumberOut,
        protected int $placeNumberOut
    ) {
        parent::__construct($poolUser, $transferPeriod, $lineNumberOut, $placeNumberOut);
    }
}
