<?php

declare(strict_types=1);

namespace SuperElf\Pool\User\ViewPeriodPerson;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?PoolUserViewPeriodPerson
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function findOneBy(array $criteria, array $orderBy = null): ?PoolUserViewPeriodPerson
    {
        return parent::findOneBy($criteria, $orderBy);
    }
}
