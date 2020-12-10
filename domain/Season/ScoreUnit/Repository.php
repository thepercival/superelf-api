<?php

declare(strict_types=1);

namespace SuperElf\Season\ScoreUnit;

use Sports\Season;
use SuperElf\Season\ScoreUnit as BaseScoreUnit;
use SuperElf\Season\ScoreUnit as SeasonScoreUnit;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?BaseScoreUnit
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
