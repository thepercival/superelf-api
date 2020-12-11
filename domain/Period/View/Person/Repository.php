<?php

declare(strict_types=1);

namespace SuperElf\Period\View\Person;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use SuperElf\Period\View as ViewPeriod;
use Sports\Team;
use SuperElf\Period\View\Person as ViewPeriodPerson;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?ViewPeriodPerson
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function findOneBy(array $criteria, array $orderBy = null): ?ViewPeriodPerson
    {
        return parent::findOneBy($criteria, $orderBy);
    }

    /**
     * @param ViewPeriod $viewPeriod
     * @param Team|null $team
     * @param int|null $line
     * @param int|null $maxRows
     * @return array|ViewPeriodPerson[]
     */
    public function findByExt(ViewPeriod $viewPeriod, Team $team = null, int $line = null, int $maxRows = null)
    {
        $qb = $this->createQueryBuilder('vpp')
            ->distinct()
            ->join("vpp.person", "p")
            ->join('Sports\Team\Player', 'pl', 'WITH', 'p = pl.person')
            ->where('vpp.viewPeriod = :viewPeriod')
        ;

        $qb = $qb->setParameter('viewPeriod', $viewPeriod );
        if( $team !== null ) {
            $qb = $qb->andWhere('pl.team = :team' );
            $qb = $qb->setParameter('team', $team );
        }
        if( $line !== null ) {
            $qb = $qb->andWhere('BIT_AND(pl.line, :line) = pl.line');
            $qb = $qb->setParameter('line', $line );
        }
        if( $maxRows !== null ) {
            $qb = $qb->setMaxResults($maxRows );
        }
        // $sql = $qb->getQuery()->getSQL();
        return $qb->getQuery()->getResult();
    }
}
