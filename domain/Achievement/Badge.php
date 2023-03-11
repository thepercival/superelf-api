<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use SuperElf\Pool\User as PoolUser;
use Sports\Competition;
use SuperElf\Achievement as AchievementBase;

class Badge extends AchievementBase implements \Stringable
{
    public function __construct(
        protected BadgeCategory $category,
        protected Competition|null $competition,
        int $rank,
        PoolUser $poolUser,
        \DateTimeImmutable $createDateTime)
    {
        parent::__construct($rank, $poolUser,$createDateTime);
    }

    public function getCompetiton(): Competition|null {
        return $this->competition;
    }

    public function getCategory(): BadgeCategory {
        return $this->category;
    }

    public function __toString(): string {
        $badge = 'badge("' . $this->category->value . '")';
        $asignedTo = ' assigned to "' . $this->poolUser->getUser()->getName() . '"';
        $pool = ' for pool "' . $this->poolUser->getPool()->getName() . '"';
        if( $this->competition === null ) {
            $competition = ' for all pool-competitions';
        } else {
            $competition = ' for competition "' . $this->competition->getName() . '"';
        }

        $asignedAt = ' at "' . $this->createDateTime->format(DATE_ATOM) . '"';
        return $badge . $asignedTo . $pool . $competition . $asignedAt;
    }
}

