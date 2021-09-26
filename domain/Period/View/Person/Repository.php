<?php
declare(strict_types=1);

namespace SuperElf\Period\View\Person;

use Doctrine\ORM\EntityRepository;
use SuperElf\Period\View as ViewPeriod;
use Sports\Team;
use SuperElf\Period\View\Person as ViewPeriodPerson;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<ViewPeriodPerson>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<ViewPeriodPerson>
     */
    use BaseRepository;

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
