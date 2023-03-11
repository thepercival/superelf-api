<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use SportsHelpers\Identifiable;
use SuperElf\Pool\User as PoolUser;

class Unviewed extends Identifiable
{
    public function __construct(protected PoolUser $poolUser)
    {
    }

    public function getPoolUser(): PoolUser {
        return $this->poolUser;
    }
}
