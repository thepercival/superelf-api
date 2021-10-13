<?php
declare(strict_types=1);

namespace SuperElf\Period\Transfer;

use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Player as S11Player;
use SuperElf\Pool\User as PoolUser;

class SubstituteUpdate extends Action
{
    public function __construct(
        PoolUser $poolUser,
        TransferPeriod $transferPeriod,
        S11Player $playerOut
    )
    {
        parent::__construct($poolUser, $transferPeriod, $playerOut);
    }
}
