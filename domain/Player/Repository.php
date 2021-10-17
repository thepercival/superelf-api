<?php
declare(strict_types=1);

namespace SuperElf\Player;

use Doctrine\ORM\EntityRepository;
use SuperElf\Period\View as ViewPeriod;
use Sports\Team;
use SuperElf\Player as S11Player;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<S11Player>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<S11Player>
     */
    use BaseRepository;

    /**
     * @param ViewPeriod $viewPeriod
     * @param Team|null $team
     * @param int|null $line
     * @param int|null $maxRows
     * @return list<S11Player>
     */
    public function findByExt(ViewPeriod $viewPeriod, Team $team = null, int $line = null, int $maxRows = null): array
    {
        $qb = $this->createQueryBuilder('s11pl')
            ->distinct()
            ->join("s11pl.person", "p")
            ->join('Sports\Team\Player', 'pl', 'WITH', 'p = pl.person')
            ->where('s11pl.viewPeriod = :viewPeriod')
        ;

        $qb = $qb->setParameter('viewPeriod', $viewPeriod);
        if ($team !== null) {
            $qb = $qb->andWhere('pl.team = :team');
            $qb = $qb->setParameter('team', $team);
        }
        if ($line !== null) {
            $qb = $qb->andWhere('BIT_AND(pl.line, :line) = pl.line');
            $qb = $qb->setParameter('line', $line);
        }
        if ($maxRows !== null) {
            $qb = $qb->setMaxResults($maxRows);
        }
        $qb = $qb->orderBy('s11pl.totalPoints', 'desc');
        // $sql = $qb->getQuery()->getSQL();
        /** @var list<S11Player> $players */
        $players = $qb->getQuery()->getResult();
        return $players;
    }
}
