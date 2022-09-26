<?php

declare(strict_types=1);

namespace SuperElf\ChatMessage\Unread;

use Doctrine\ORM\EntityRepository;
use Sports\Poule;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\ChatMessage\Unread as UnreadChatMessage;
use SuperElf\Pool\User as PoolUser;

/**
 * @template-extends EntityRepository<UnreadChatMessage>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<UnreadChatMessage>
     */
    use BaseRepository;

    public function findNrOfUnread(Poule $poule, PoolUser $poolUser): int
    {
        $qb = $this->createQueryBuilder('ucm')
            ->select('count(ucm.id)')
            ->join('ucm.chatMessage', 'cm')
            ->where('ucm.poolUser = :poolUser')
            ->where('cm.poule = :poule');
        $qb = $qb->setParameter('poolUser', $poolUser);
        $qb = $qb->setParameter('poule', $poule);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

}
