<?php

declare(strict_types=1);

namespace SuperElf\Achievement\Trophy;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Achievement\Trophy;
use SuperElf\Pool\User as PoolUser;
use SuperElf\PoolCollection;

/**
 * @template-extends EntityRepository<Trophy>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Trophy>
     */
    use BaseRepository;

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
