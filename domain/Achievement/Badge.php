<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use SuperElf\Pool\User as PoolUser;
use Sports\Competition;
use SuperElf\Achievement as AchievementBase;

class Badge extends AchievementBase
{
    public function __construct(
        protected BadgeCategory $category,
        Competition|null $competition,
        int $rank,
        PoolUser $poolUser,
        \DateTimeImmutable $createDateTime)
    {
        parent::__construct($competition, $rank, $poolUser,$createDateTime);
    }

    public function getCategory(): BadgeCategory {
        return $this->category;
    }
}

