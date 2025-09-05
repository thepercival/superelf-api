<?php

declare(strict_types=1);

namespace SuperElf\Statistics;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Statistics;
use SuperElf\Formation as S11Formation;

/**
 * @template-extends EntityRepository<Statistics>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Statistics>
     */
    use BaseRepository;

    /**
     * @param S11Formation $formation
     * @param int $gameRoundNr
     * @return list<Statistics>
     */
    public function findByFormationGameRound(S11Formation $formation, int $gameRoundNr): array
    {
        $query = $this->createQueryBuilder('s')
            ->distinct()
            ->join('s.gameRound', 'gr')
            ->join('s.player', 'p')
            ->join('SuperElf\Formation\Place', 'fp', 'WITH', 'p = fp.player')
            ->join('fp.formationLine', 'fl')
            ->where('fl.formation = :formation')
            ->andWhere('gr.number = :gameRoundNr')
        ;

        $query = $query->setParameter('formation', $formation );
        $query = $query->setParameter('gameRoundNr', $gameRoundNr );

        /** @var list<Statistics> $stats */
        $stats = $query->getQuery()->getResult();
        return $stats;
    }
}
