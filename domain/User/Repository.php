<?php

declare(strict_types=1);

namespace SuperElf\User;

use SuperElf\User;

class Repository extends \Sports\Repository
{
    public function find($id, $lockMode = null, $lockVersion = null): ?User
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
