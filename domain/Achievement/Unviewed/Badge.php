<?php

declare(strict_types=1);

namespace SuperElf\Achievement\Unviewed;

use SuperElf\Achievement\Unviewed as UnviewedBase;
use SuperElf\Achievement\Badge as BadgeBase;
use SuperElf\Pool\User as PoolUser;

class Badge extends UnviewedBase
{
    public function __construct(PoolUser $poolUser, protected BadgeBase $badge)
    {
        parent::__construct($poolUser);
    }

    public function getBadge(): BadgeBase {
        return $this->badge;
    }
}
