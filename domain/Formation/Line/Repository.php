<?php

namespace SuperElf\Formation\Line;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Period\View\Person as ViewPeriodPerson;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?FormationLine
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function findOneBy(array $criteria, array $orderBy = null): ?FormationLine
    {
        return parent::findOneBy($criteria, $orderBy);
    }

    /**
     * @param int $line
     * @param ViewPeriodPerson $viewPeriodPerson
     * @return array|FormationLine[]
     */
    public function findByExt(int $line, ViewPeriodPerson $viewPeriodPerson  )
    {
        $exprExists = $this->getEM()->getExpressionBuilder();

        $query = $this->createQueryBuilder('fl')
            ->distinct()
            ->join('fl.formation', 'f')
            ->join('fl.viewPeriodPersons', 'vpp')
            ->where('fl.number = :line')
            ->andWhere('vpp = :viewPeriodPerson')

//            ->andWhere(
//                $exprExists->exists(
//                    $this->getEM()->createQueryBuilder()
//                        ->select('gr.id')
//                        ->from('SuperElf\Formation\Line\ViewPeriodPerson', 'flvpp')
//                        ->where('flvpp.viewPeriodPerson = :viewPeriodPerson')
//                        ->getDQL()
//                )
//            )
        ;
        $query = $query->setParameter('line', $line );
        $query = $query->setParameter('viewPeriodPerson', $viewPeriodPerson );

        return $query->getQuery()->getResult();
    }
}
