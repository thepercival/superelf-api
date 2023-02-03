<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Person;
use Sports\Sport\FootballLine;
use Sports\Team\Player;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Periods\TransferPeriod\Action;
use SuperElf\Pool\User as PoolUser;

class Replacement extends Action
{
    public function __construct(
        PoolUser $poolUser,
        TransferPeriod $transferPeriod,
        protected FootballLine $lineNumberOut,
        protected int $placeNumberOut,
        protected Player $playerIn
    )
    {
        parent::__construct($poolUser, $transferPeriod, $lineNumberOut, $placeNumberOut);
        if (!$poolUser->getReplacements()->contains($this)) {
            $poolUser->getReplacements()->add($this);
        }
    }

    public function getPlayerIn(): Player
    {
        return $this->playerIn;
    }

    public function getPersonIn(): Person {
        return $this->getPlayerIn()->getPerson();
    }
}
