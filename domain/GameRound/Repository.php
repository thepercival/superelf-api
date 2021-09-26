<?php
declare(strict_types=1);

namespace SuperElf\GameRound;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use Sports\Competition;
use SuperElf\GameRound as BaseGameRound;

/**
 * @template-extends EntityRepository<BaseGameRound>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<BaseGameRound>
     */
    use BaseRepository;

    public function findOneByNumber( Competition $competition, int $gameRoundNumber): ?BaseGameRound
    {
        $query = $this->createQueryBuilder('gr')
            ->join('gr.viewPeriod', 'vp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere('gr.number = :gameRound')
        ;
        $query = $query->setParameter('competition', $competition );
        $query = $query->setParameter('gameRound', $gameRoundNumber );

        $gameRounds = $query->getQuery()->getResult();
        if (count($gameRounds) === 0) {
            return null;
        }
        if (count($gameRounds) > 1) {
            throw new \Exception("gameround should only be in one viewperiod", E_ERROR );
        }
        return reset($gameRounds);
    }
}
