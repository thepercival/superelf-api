<?php

declare(strict_types=1);

namespace SuperElf\Achievement\Unviewed;

use SuperElf\Achievement\Unviewed as UnviewedBase;
use SuperElf\Achievement\Trophy as TrophyBase;
use SuperElf\Pool\User as PoolUser;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Trophy extends UnviewedBase
{
    public function __construct(PoolUser $poolUser, protected TrophyBase $trophy)
    {
        parent::__construct($poolUser);
    }

    public function getTrophy(): TrophyBase {
        return $this->trophy;
    }
}
