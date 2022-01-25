<?php

declare(strict_types=1);

namespace SuperElf;

use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\Transfer\Action;
use SuperElf\Pool\User as PoolUser;

class Substitution extends Action
{
    public function __construct(
        PoolUser $poolUser,
        TransferPeriod $transferPeriod,
        FormationPlace $formationPlace
    ) {
        parent::__construct($poolUser, $transferPeriod, $formationPlace);
    }
}
