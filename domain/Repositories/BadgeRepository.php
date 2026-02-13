<?php

declare(strict_types=1);

namespace SuperElf\Repositories;

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
