<?php

declare(strict_types=1);

namespace SuperElf\Achievement\Unviewed\Trophy;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Achievement\Unviewed\Trophy as UnviewedTrophy;
use SuperElf\Pool\User as PoolUser;

/**
 * @template-extends EntityRepository<UnviewedTrophy>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<UnviewedTrophy>
     */
    use BaseRepository;

    /**
     * @param PoolUser $poolUser
     * @return list<UnviewedTrophy>
     */
    public function findByPoolUser(PoolUser $poolUser): array
    {
        $queryBuilder = $this->createQueryBuilder('ut')
            ->join("ut.trophy", "t")
            ->join("t.poolUser", "pu")
            ->join("pu.pool", "p")
            ->join("ut.poolUser", "upu")
            ->where('p.collection = :poolCollection')
            ->andWhere('upu.user = :user')
            ->andWhere('ut.poolUser = :user')
            ->setParameter('poolCollection', $poolUser->getPool()->getCollection())
            ->setParameter('user', $poolUser->getUser());

        /** @var list<UnviewedTrophy> $badges */
        $badges = $queryBuilder->getQuery()->getResult();
        return $badges;
    }
}
