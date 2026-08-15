<?php

declare(strict_types=1);

namespace App\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Against as AgainstGame;
use SuperElf\Game\MissingPlayer;

/**
 * @template-extends EntityRepository<MissingPlayer>
 */
final class GameMissingPlayerRepository extends EntityRepository
{
    /**
     * @return list<MissingPlayer>
     */
    public function findByGame(AgainstGame $game): array
    {
        /** @var list<MissingPlayer> $missingPlayers */
        $missingPlayers = $this->createQueryBuilder('missingPlayer')
            ->join('missingPlayer.againstGamePlace', 'gamePlace')
            ->andWhere('gamePlace.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getResult();
        return $missingPlayers;
    }

    public function removeByGame(AgainstGame $game): void
    {
        foreach ($this->findByGame($game) as $missingPlayer) {
            $this->getEntityManager()->remove($missingPlayer);
        }
        $this->getEntityManager()->flush();
    }
}
