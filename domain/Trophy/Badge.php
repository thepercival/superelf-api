<?php

declare(strict_types=1);

namespace SuperElf\Trophy;

use SuperElf\Pool\User as PoolUser;
use Sports\Competition;
use SuperElf\Trophy;

class Badge extends Trophy
{
    public function __construct(protected BadgeCategory $category, Competition|null $competition, int $rank, PoolUser $poolUser)
    {
        parent::__construct($competition, $rank, $poolUser);
    }

    public function getCategory(): BadgeCategory {
        return $this->category;
    }
}

