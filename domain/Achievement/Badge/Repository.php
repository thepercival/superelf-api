<?php

declare(strict_types=1);

namespace SuperElf\Achievement\Badge;

use Doctrine\ORM\EntityRepository;
use SuperElf\Pool\User as PoolUser;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Achievement\Badge;
use SuperElf\PoolCollection;

/**
 * @template-extends EntityRepository<Badge>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Badge>
     */
    use BaseRepository;

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
