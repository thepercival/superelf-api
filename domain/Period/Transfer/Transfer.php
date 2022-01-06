<?php

declare(strict_types=1);

namespace SuperElf\Period\Transfer;

use Sports\Person;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Player as S11Player;
use SuperElf\Pool\User as PoolUser;

class Transfer extends Action
{
    public function __construct(
        PoolUser $poolUser,
        TransferPeriod $transferPeriod,
        S11Player $playerOut,
        protected Person $personIn
    )
    {
        parent::__construct($poolUser, $transferPeriod, $playerOut);
        if (!$poolUser->getTransfers()->contains($this)) {
            $poolUser->getTransfers()->add($this);
        }
    }

    public function getPersonIn(): Person
    {
        return $this->personIn;
    }
}
