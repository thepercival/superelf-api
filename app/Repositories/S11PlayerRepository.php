<?php

declare(strict_types=1);

namespace App\Repositories;

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
            ->where('s11pl.viewPeriod = :viewPeriod');

        $qb = $qb->setParameter('viewPeriod', $viewPeriod);

        $qb = $qb->orderBy('s11pl.totalPoints', 'desc');
        /** @var list<S11Player> $players */
        $players = $qb->getQuery()->getResult();
        return $players;
    }

    /**
     * @param ViewPeriod $viewPeriod
    * @param list<Team>|null $teams
     * @param AgainstGame|null $game
     * @param int|null $line
     * @param int|null $maxRows
    * @param bool $orderByPoints
     * @return list<S11Player>
     */
    public function findByExt(
        ViewPeriod $viewPeriod,
        array|null $teams,
        AgainstGame|null $game,
        int|null $line = null,
        int|null $maxRows = null,
        bool $orderByPoints = false
    ): array {
        $qb = $this->createQueryBuilder('s11pl')
            ->distinct()
            ->join("s11pl.person", "p")
            ->join("s11pl.viewPeriod", "vp")
            ->join('Sports\Team\Player', 'pl', 'WITH', 'p = pl.person')
            ->join('s11pl.totals', 'plt')
            ->where('pl.startDateTime < vp.endDateTime')
            ->andWhere('pl.endDateTime > vp.startDateTime')
            ->andWhere('s11pl.viewPeriod = :viewPeriod');
        $qb = $qb->setParameter('viewPeriod', $viewPeriod);

        if ($teams !== null) {
            $qb = $qb->andWhere('pl.team IN (:teams)');
            $qb = $qb->setParameter('teams', $teams);
        }

        if ($game !== null) {
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

        if ($orderByPoints) {
            $qb = $qb->orderBy('s11pl.totalPoints', 'desc');
        } else {
            $qb = $qb->orderBy('plt.nrOfTimesStarted', 'desc');
            $qb = $qb->addOrderBy('plt.nrOfTimesSubstituted', 'asc');
        }

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
