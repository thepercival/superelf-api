<?php

declare(strict_types=1);

namespace SuperElf\PoolCollection;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use SuperElf\PoolCollection;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?PoolCollection
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
