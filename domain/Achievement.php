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
        protected PoolUser $poolUser,
        protected \DateTimeImmutable $createDateTime
    ) {

    }

    public function getPoolUser(): PoolUser {
        return $this->poolUser;
    }

    public function getCreateDateTime(): DateTimeImmutable
    {
        return $this->createDateTime;
    }
}
