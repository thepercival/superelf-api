<?php

declare(strict_types=1);

namespace SuperElf\ScoutedPerson;

use SuperElf\ScoutedPerson;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?ScoutedPerson
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
