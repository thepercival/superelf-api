<?php

declare(strict_types=1);

namespace SuperElf\Achievement\Unviewed\Badge;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Achievement\Unviewed\Badge as UnviewedBadge;
use SuperElf\Pool\User as PoolUser;

/**
 * @template-extends EntityRepository<UnviewedBadge>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<UnviewedBadge>
     */
    use BaseRepository;

    /**
     * @param PoolUser $poolUser
     * @return list<UnviewedBadge>
     */
    public function findByPoolUser(PoolUser $poolUser): array
    {
        $queryBuilder = $this->createQueryBuilder('ub')
            ->join("ub.badge", "b")
            ->join("b.poolUser", "pu")
            ->join("pu.pool", "p")
            ->join("ub.poolUser", "upu")
            ->where('p.collection = :poolCollection')
            ->andWhere('upu.user = :user')
            ->setParameter('poolCollection', $poolUser->getPool()->getCollection())
            ->setParameter('user', $poolUser->getUser());

        /** @var list<UnviewedBadge> $badges */
        $badges = $queryBuilder->getQuery()->getResult();
        return $badges;
    }
}
