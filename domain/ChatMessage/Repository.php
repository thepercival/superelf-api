<?php

declare(strict_types=1);

namespace SuperElf\ChatMessage;

use Doctrine\ORM\EntityRepository;
use Sports\Poule;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\ChatMessage;
use SuperElf\Pool;

/**
 * @template-extends EntityRepository<ChatMessage>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<ChatMessage>
     */
    use BaseRepository;

    /**
     * @param Poule $poule
     * @param Pool $pool
     * @return list<ChatMessage>
     */
    public function findByExt(Poule $poule, Pool $pool): array
    {
        $qb = $this->createQueryBuilder('cm')
            ->join('cm.poule', 'p')
            ->join('p.round', 'r')
            ->join('r.structureCell', 'sc')
            ->join('sc.roundNumber', 'rn')
            ->join('rn.competition', 'c')
            ->join('c.league', 'l')
            ->join('l.association', 'a')
            ->join('SuperElf\PoolCollection', 'pc', 'WITH', 'a = pc.association')
            ->join('SuperElf\Pool', 'pools', 'WITH', 'pc = pools.collection')
            ->where('cm.poule = :poule');
        $qb = $qb->setParameter('poule', $poule);

        $qb = $qb->andWhere('pools = :pool');
        $qb = $qb->setParameter('pool', $pool);
        $qb = $qb->orderBy('cm.dateTime', 'desc');
        // $sql = $qb->getQuery()->getSQL();
        /** @var list<ChatMessage> $messages */
        $messages = $qb->getQuery()->getResult();
        return $messages;
    }
}
