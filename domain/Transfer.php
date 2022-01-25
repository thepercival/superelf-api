<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Person;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\Transfer\Action;
use SuperElf\Pool\User as PoolUser;

class Transfer extends Action
{
    public function __construct(
        PoolUser $poolUser,
        TransferPeriod $transferPeriod,
        FormationPlace $formationPlace,
        protected Person $personIn
    )
    {
        parent::__construct($poolUser, $transferPeriod, $formationPlace);
        if (!$poolUser->getTransfers()->contains($this)) {
            $poolUser->getTransfers()->add($this);
        }
    }

    public function getPersonIn(): Person
    {
        return $this->personIn;
    }

//    public function outHasTeam(): bool
//    {
//        $seasonPeriod = $this->poolUser->getPool()->getSeason()->getPeriod();
//        $this->formationPlace->getPlayer()->
//        return $this->personIn->get();
//    }
}
