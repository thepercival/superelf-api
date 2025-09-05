<?php

declare(strict_types=1);

namespace SuperElf\ChatMessage\Unread;

use Doctrine\ORM\EntityRepository;
use Sports\Poule;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\ChatMessage;
use SuperElf\ChatMessage\Unread as UnreadChatMessage;
use SuperElf\Pool\User as PoolUser;

/**
 * @template-extends EntityRepository<UnreadChatMessage>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<UnreadChatMessage>
     */
    use BaseRepository;

    /**
     * @param Poule $poule
     * @param PoolUser $poolUser
     * @return list<UnreadChatMessage>
     */
    protected function findUnread(Poule $poule, PoolUser $poolUser): array
    {
        $qb = $this->createQueryBuilder('ucm')
            ->join('ucm.chatMessage', 'cm')
            ->where('ucm.poolUser = :poolUser')
            ->andWhere('cm.poule = :poule');
        $qb = $qb->setParameter('poolUser', $poolUser);
        $qb = $qb->setParameter('poule', $poule);

        // $sql = $qb->getQuery()->getSQL();
        /** @var list<UnreadChatMessage> $messages */
        $messages = $qb->getQuery()->getResult();
        return $messages;
    }

    public function findNrOfUnread(Poule $poule, PoolUser $poolUser): int
    {
        $qb = $this->createQueryBuilder('ucm')
            ->select('count(ucm.id)')
            ->join('ucm.chatMessage', 'cm')
            ->where('ucm.poolUser = :poolUser')
            ->andWhere('cm.poule = :poule');
        $qb = $qb->setParameter('poolUser', $poolUser);
        $qb = $qb->setParameter('poule', $poule);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param ChatMessage $chatMessage
     * @param list<PoolUser> $poolUsers
     */
    public function saveUnreadMessages(ChatMessage $chatMessage, array $poolUsers): void {
        foreach( $poolUsers as $poolUser) {
            $unreadChatMessage = new UnreadChatMessage($chatMessage, $poolUser);
            $this->save($unreadChatMessage, true);
        }
    }

    public function removeUnreadMessages(PoolUser $poolUser, Poule $poule): void {

        foreach( $this->findUnread($poule, $poolUser) as $unreadMessage) {
            $this->remove($unreadMessage, true);
        }
    }
}
