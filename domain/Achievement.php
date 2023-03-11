<?php

declare(strict_types=1);

namespace SuperElf;

use DateTimeImmutable;
use SportsHelpers\Identifiable;
use SuperElf\Pool\User as PoolUser;
use Sports\Competition;

class Achievement extends Identifiable
{
    public function __construct(
        protected int $rank,
        protected PoolUser $poolUser,
        protected \DateTimeImmutable $createDateTime
    ) {

    }

    public function getRank(): int {
        return $this->rank;
    }

    public function getPoolUser(): PoolUser {
        return $this->poolUser;
    }

    public function getCreateDateTime(): DateTimeImmutable
    {
        return $this->createDateTime;
    }
}
