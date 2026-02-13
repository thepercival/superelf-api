<?php

declare(strict_types=1);

namespace SuperElf\Repositories;

use Doctrine\ORM\EntityRepository;
use SuperElf\Achievement\Trophy;
use SuperElf\PoolCollection;

/**
 * @template-extends EntityRepository<Trophy>
 */
final class TrophyRepository extends EntityRepository
{
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
