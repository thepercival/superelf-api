<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use SuperElf\Pool\User as PoolUser;
use Sports\Competition;
use SuperElf\Achievement as AchievementBase;

class Trophy extends AchievementBase  implements \Stringable
{
    public function __construct(
        protected Competition $competition,
        protected int $rank,
        PoolUser $poolUser,
        \DateTimeImmutable $createDateTime)
    {
        parent::__construct($poolUser,$createDateTime);
    }

    public function getCompetiton(): Competition {
        return $this->competition;
    }

    public function getRank(): int {
        return $this->rank;
    }


    public function __toString(): string {
        $asignedTo = ' assigned to "' . $this->poolUser->getUser()->getName() . '"';
        $pool = ' for pool "' . $this->poolUser->getPool()->getName() . '"';
        $competition = ' for competition "' . $this->competition->getName() . '"';
        $asignedAt = ' at "' . $this->createDateTime->format('Y-m-d') . '"';
        return $asignedTo . $pool . $competition . $asignedAt;
    }
}
