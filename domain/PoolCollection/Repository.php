<?php
declare(strict_types=1);

namespace SuperElf\PoolCollection;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\PoolCollection;

/**
 * @template-extends EntityRepository<PoolCollection>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<PoolCollection>
     */
    use BaseRepository;

    public function findOneByName(string $name): PoolCollection|null
    {
        $query = $this->createQueryBuilder('pc')
            ->join("pc.association", "a")
        ;
        $query = $query->where('a.name = :name');
        $query = $query->setParameter('name', $name);


        /** @var list<PoolCollection> $poolCollections */
        $poolCollections = $query->getQuery()->getResult();

        if (count($poolCollections) > 1) {
            throw new \Exception('there can only be 1 poolcollection with a certain name', E_ERROR);
        }
        $poolCollection = reset($poolCollections);
        return $poolCollection === false ? null : $poolCollection;
    }
}
