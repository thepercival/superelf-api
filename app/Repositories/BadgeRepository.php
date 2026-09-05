<?php

declare(strict_types=1);

namespace App\Repositories;

use Doctrine\ORM\EntityRepository;
use SuperElf\Achievement\Badge;
use SuperElf\Pool\User as PoolUser;
use SuperElf\PoolCollection;

/**
 * @template-extends EntityRepository<Badge>
 */
final class BadgeRepository extends EntityRepository
{
    /**
     * @return list<array{userId: int|string, achievementCount: int|string}>
     */
    public function findCountsByPoolCollection(PoolCollection $poolCollection): array
    {
        /** @var list<array{userId: int|string, achievementCount: int|string}> $counts */
        $counts = $this->createQueryBuilder('b')
            ->select('IDENTITY(pu.user) AS userId', 'COUNT(b.id) AS achievementCount')
            ->join('b.poolUser', 'pu')
            ->join('pu.pool', 'p')
            ->where('p.collection = :poolCollection')
            ->setParameter('poolCollection', $poolCollection)
            ->groupBy('pu.user')
            ->getQuery()
            ->getArrayResult();
        return $counts;
    }

    /**
     * @param PoolUser $poolUser
     * @return list<Badge>
     */
    public function findByPoolCollection(PoolCollection $poolCollection): array
    {
        $queryBuilder = $this->createQueryBuilder('b')
            ->join("b.poolUser", "pu")
            ->join("pu.pool", "p")
            ->where('p.collection = :poolCollection')
            ->setParameter('poolCollection', $poolCollection);

        /** @var list<Badge> $badges */
        $badges = $queryBuilder->getQuery()->getResult();
        return $badges;
    }
}
