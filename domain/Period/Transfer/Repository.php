<?php

declare(strict_types=1);

namespace SuperElf\Period\Transfer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use SuperElf\Period\Transfer as TransferPeriod;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?TransferPeriod
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }
}
