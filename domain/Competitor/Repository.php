<?php

declare(strict_types=1);

namespace SuperElf\Competitor;

use SuperElf\Competitor;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?Competitor
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
