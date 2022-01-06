<?php

declare(strict_types=1);

namespace SuperElf\ScoutedPlayer;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\ScoutedPlayer;
use SuperElf\User;

/**
 * @template-extends EntityRepository<ScoutedPlayer>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<ScoutedPlayer>
     */
    use BaseRepository;

    /**
     * @param User $user
     * @param ViewPeriod $viewPeriod
     * @return list<ScoutedPlayer>
     */
    public function findByExt(User $user, ViewPeriod $viewPeriod): array
    {
        $query = $this->createQueryBuilder('scpl')
            ->join("scpl.s11Player", "s11pl")
        ;

        $query = $query->where('scpl.user = :user');
        $query = $query->setParameter('user', $user);

        $query = $query->andWhere('s11pl.viewPeriod = :viewPeriod');
        $query = $query->setParameter('viewPeriod', $viewPeriod);

        /** @var list<ScoutedPlayer> $scoutedPlayers */
        $scoutedPlayers = $query->getQuery()->getResult();
        return $scoutedPlayers;
    }
}
