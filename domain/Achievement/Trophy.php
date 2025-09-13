<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use SuperElf\Pool\User as PoolUser;
use Sports\Competition;
use SuperElf\Achievement as AchievementBase;

class Trophy extends AchievementBase
{
    public function __construct(
        protected Competition $competition,
        PoolUser $poolUser,
        \DateTimeImmutable $createDateTime)
    {
        parent::__construct($poolUser,$createDateTime);
    }

    public function getCompetiton(): Competition {
        return $this->competition;
    }

    public function getPoolId(): string|int|null {
        return $this->poolUser->getPool()->getId();
    }

    public function showDescription(): string {
        $asignedTo = ' assigned to "' . $this->poolUser->getUser()->getName() . '"';
        $pool = ' for pool "' . $this->poolUser->getPool()->getName() . '"';
        $competition = ' for competition "' . $this->competition->getName() . '"';
        $asignedAt = ' at "' . $this->createDateTime->format('Y-m-d') . '"';
        return $asignedTo . $pool . $competition . $asignedAt;
    }
}
