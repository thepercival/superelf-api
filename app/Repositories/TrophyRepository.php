<?php

declare(strict_types=1);

namespace App\Repositories;

use Doctrine\ORM\EntityRepository;
use SuperElf\Achievement\Trophy;
use SuperElf\PoolCollection;

/**
 * @template-extends EntityRepository<Trophy>
 */
final class TrophyRepository extends EntityRepository
{
    /**
     * @return list<array{userId: int|string, leagueName: string, achievementCount: int|string}>
     */
    public function findCountsByPoolCollection(PoolCollection $poolCollection): array
    {
        /** @var list<array{userId: int|string, leagueName: string, achievementCount: int|string}> $counts */
        $counts = $this->createQueryBuilder('t')
            ->select('IDENTITY(pu.user) AS userId', 'l.name AS leagueName', 'COUNT(t.id) AS achievementCount')
            ->join('t.poolUser', 'pu')
            ->join('pu.pool', 'p')
            ->join('t.competition', 'c')
            ->join('c.league', 'l')
            ->where('p.collection = :poolCollection')
            ->setParameter('poolCollection', $poolCollection)
            ->groupBy('pu.user', 'l.name')
            ->getQuery()
            ->getArrayResult();
        return $counts;
    }

    /**
     * @param PoolCollection $poolCollection
     * @return list<Trophy>
     */
    public function findByPoolCollection(PoolCollection $poolCollection): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->join("t.poolUser", "pu")
            ->join("pu.pool", "p")
            ->where('p.collection = :poolCollection')
            ->setParameter('poolCollection', $poolCollection);

        /** @var list<Trophy> $trophies */
        $trophies = $queryBuilder->getQuery()->getResult();
        return $trophies;
    }
}
