<?php

declare(strict_types=1);

namespace SuperElf;

use SportsHelpers\Identifiable;
use SuperElf\Pool\User as PoolUser;
use Sports\Competition;

class Trophy extends Identifiable
{
    public function __construct(
        protected Competition|null  $competition,
        protected int $rank,
        protected PoolUser $poolUser
    ) {
    }

    public function getCompetiton(): Competition|null {
        return $this->competition;
    }

    public function getRank(): int {
        return $this->rank;
    }

    public function getPoolUser(): PoolUser {
        return $this->poolUser;
    }
}
