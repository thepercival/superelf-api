<?php

declare(strict_types=1);

namespace SuperElf\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Against as AgainstGame;
use Sports\Team;
use Sports\Team\Player as TeamPlayer;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\S11Player as S11Player;

/**
 * @template-extends EntityRepository<S11Player>
 */
final class S11PlayerRepository extends EntityRepository
{
    /**
     * @param ViewPeriod $viewPeriod
     * @return list<S11Player>
     */
    public function findByViewPeriod(ViewPeriod $viewPeriod): array
    {
        $qb = $this->createQueryBuilder('s11pl')
            ->distinct()
            ->join("s11pl.person", "p")
            ->join('Sports\Team\Player', 'pl', 'WITH', 'p = pl.person')
            ->where('s11pl.viewPeriod = :viewPeriod')
        ;

        $qb = $qb->setParameter('viewPeriod', $viewPeriod);

        $qb = $qb->orderBy('s11pl.totalPoints', 'desc');
        /** @var list<S11Player> $players */
        $players = $qb->getQuery()->getResult();
        return $players;
    }

    /**
     * @param ViewPeriod $viewPeriod
     * @param Team $team
     * @param AgainstGame|null $game
     * @param int|null $line
     * @param int|null $maxRows
     * @return list<S11Player>
     */
    public function findByExt(
        ViewPeriod $viewPeriod,
        Team|null $team,
        AgainstGame|null $game,
        int|null $line = null,
        int|null $maxRows = null): array
    {
        $qb = $this->createQueryBuilder('s11pl')
            ->distinct()
            ->join("s11pl.person", "p")
            ->join("s11pl.viewPeriod", "vp")
            ->join('Sports\Team\Player', 'pl', 'WITH', 'p = pl.person')
            ->where('pl.startDateTime < vp.endDateTime')
            ->andWhere('pl.endDateTime > vp.startDateTime')
            ->andWhere('s11pl.viewPeriod = :viewPeriod')
        ;
        $qb = $qb->setParameter('viewPeriod', $viewPeriod);

        if( $team !== null) {
            $qb = $qb->andWhere('pl.team = :team');
            $qb = $qb->setParameter('team', $team);
        }

        if( $game !== null) {
            $qb = $qb->andWhere('pl.startDateTime <= :dateTime');
            $qb = $qb->andWhere('pl.endDateTime > :dateTime');
            $qb = $qb->setParameter('dateTime', $game->getStartDateTime());
        }

        if ($line !== null) {
            $qb = $qb->andWhere('BIT_AND(pl.line, :line) = pl.line');
            $qb = $qb->setParameter('line', $line);
        }
        if ($maxRows !== null) {
            $qb = $qb->setMaxResults($maxRows);
        }

        $qb = $qb->orderBy('s11pl.totalPoints', 'desc');
        // $sql = $qb->getQuery()->getSQL();
        /** @var list<S11Player> $players */
        $players = $qb->getQuery()->getResult();
        return $players;
    }

    /**
     * @param ViewPeriod $viewPeriod
     * @param TeamPlayer $player
     * @return S11Player|null
     */
    public function findOneByExt(ViewPeriod $viewPeriod, TeamPlayer $player): S11Player|null
    {
        $qb = $this->createQueryBuilder('s11pl');
        $qb = $qb->where('s11pl.viewPeriod = :viewPeriod');
        $qb = $qb->setParameter('viewPeriod', $viewPeriod);
        $qb = $qb->andWhere('s11pl.player = :player');
        $qb = $qb->setParameter('player', $player);
        // $sql = $qb->getQuery()->getSQL();
        /** @var S11Player|null $s11Player */
        $s11Player = $qb->getQuery()->getResult();
        return $s11Player;
    }
}