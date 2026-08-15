<?php

declare(strict_types=1);

namespace App\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Against;
use Sports\Game\Participation;
use SuperElf\Game\ParticipationStatistics;

/** @template-extends EntityRepository<ParticipationStatistics> */
final class GameParticipationStatisticsRepository extends EntityRepository
{
    public function findOneByParticipation(Participation $participation): ParticipationStatistics|null
    {
        return $this->findOneBy(['gameParticipation' => $participation]);
    }

    public function removeByGame(Against $game): void
    {
        /** @var list<ParticipationStatistics> $statistics */
        $statistics = $this->createQueryBuilder('statistics')
            ->join('statistics.gameParticipation', 'participation')
            ->join('participation.againstGamePlace', 'gamePlace')
            ->where('gamePlace.game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getResult();

        foreach ($statistics as $participationStatistics) {
            $this->getEntityManager()->remove($participationStatistics);
        }
    }
}