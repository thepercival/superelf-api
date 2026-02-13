<?php

declare(strict_types=1);

namespace SuperElf\Repositories;

use Doctrine\ORM\EntityRepository;
use SuperElf\Achievement\Unviewed\Trophy as UnviewedTrophy;
use SuperElf\Pool\User as PoolUser;

/**
 * @template-extends EntityRepository<UnviewedTrophy>
 */
final class TrophyUnviewedRepository extends EntityRepository
{
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
            ->setParameter('poolCollection', $poolUser->getPool()->getCollection())
            ->setParameter('user', $poolUser->getUser());

        /** @var list<UnviewedTrophy> $trophies */
        $trophies = $queryBuilder->getQuery()->getResult();
        return $trophies;
    }
}
