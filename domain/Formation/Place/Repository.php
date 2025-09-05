<?php

declare(strict_types=1);

namespace SuperElf\Formation\Place;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Player as S11Player;

/**
 * @template-extends EntityRepository<FormationPlace>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<FormationPlace>
     */
    use BaseRepository;

    /**
     * @param S11Player $s11Player
     * @return list<FormationPlace>
     */
    public function findByPlayer(S11Player $s11Player): array
    {
        $qb = $this->getQuery($s11Player);
        /** @var list<FormationPlace> $formationPlaces */
        $formationPlaces = $qb->getQuery()->getResult();
        return $formationPlaces;
    }

    protected function getQuery(S11Player $s11Player): QueryBuilder
    {
        return $this->createQueryBuilder('fpl')
            ->join("fpl.formationLine", "fl")
            ->join("fl.formation", "f")
            ->where('f.viewPeriod = :viewPeriod')
            ->setParameter('viewPeriod', $s11Player->getViewPeriod())
            ->andWhere('fpl.player = :player')
            ->setParameter('player', $s11Player);
        // return $this->applyExtraFilters($query, $gameStates, $gameRoundNumber);
    }
}
