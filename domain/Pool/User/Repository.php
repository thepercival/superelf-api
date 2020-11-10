<?php

declare(strict_types=1);

namespace SuperElf\Pool\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use SuperElf\Pool\User as PoolUser;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?PoolUser
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
