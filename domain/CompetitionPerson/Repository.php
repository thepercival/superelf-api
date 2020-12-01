<?php

declare(strict_types=1);

namespace SuperElf\CompetitionPerson;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use League\Period\Period;
use Sports\Competition;
use Sports\Person as PersonBase;
use Sports\Team;
use SuperElf\CompetitionPerson as BaseCompetitionPerson;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?BaseCompetitionPerson
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * @param Competition $sourceCompetition
     * @param Team|null $team
     * @param int|null $line
     * @param int|null $maxRows
     * @return array|PersonBase[]
     */
    public function findByExt(Competition $sourceCompetition, Team $team = null, int $line = null, int $maxRows = null)
    {
        $qb = $this->createQueryBuilder('cp')
            ->distinct()
            ->join("cp.person", "p")
            ->join('Sports\Team\Player', 'pl', 'WITH', 'p = pl.person')
            ->where('cp.sourceCompetition = :competition')
        ;

        $qb = $qb->setParameter('competition', $sourceCompetition );
        if( $team !== null ) {
            $qb = $qb->andWhere('pl.team = :team' );
            $qb = $qb->setParameter('team', $team );
        }
        if( $line !== null ) {
            $qb = $qb->andWhere('pl.line = :line' );
            $qb = $qb->setParameter('line', $line );
        }
        if( $maxRows !== null ) {
            $qb = $qb->setMaxResults($maxRows );
        }
        return $qb->getQuery()->getResult();
    }
}
