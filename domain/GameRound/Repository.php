<?php
declare(strict_types=1);

namespace SuperElf\GameRound;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Period\View as ViewPeriod;
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

    public function findOneByNumber(ViewPeriod $viewPeriod, int $gameRoundNumber): BaseGameRound|null
    {
        $query = $this->createQueryBuilder('gr')
            ->join('gr.viewPeriod', 'vp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere('gr.number = :gameRound')
        ;
        $query = $query->setParameter('competition', $viewPeriod->getSourceCompetition());
        $query = $query->setParameter('gameRound', $gameRoundNumber);
        /** @var list<BaseGameRound> $gameRounds */
        $gameRounds = $query->getQuery()->getResult();
        if (count($gameRounds) > 2) {
            throw new \Exception('gameround should only be in one viewperiod', E_ERROR);
        }
        $gameRound = reset($gameRounds);
        return $gameRound === false ? null : $gameRound;
    }
}
