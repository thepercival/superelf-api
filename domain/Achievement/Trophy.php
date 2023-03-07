<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use SuperElf\Pool\User as PoolUser;
use Sports\Competition;
use SuperElf\Achievement as AchievementBase;

class Trophy extends AchievementBase
{
    public function __construct(
        Competition|null  $competition,
        int $rank,
        PoolUser $poolUser,
        \DateTimeImmutable $createDateTime)
    {
        parent::__construct($competition, $rank, $poolUser,$createDateTime);
    }
}
