<?php

declare(strict_types=1);

namespace SuperElf\Period\View;

use Doctrine\ORM\EntityRepository;
use Sports\Competition;
use Sports\Poule;
use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SuperElf\Period\View as ViewPeriod;

/**
 * @template-extends EntityRepository<ViewPeriod>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<ViewPeriod>
     */
    use BaseRepository;

    public function findOneByDate(Competition $competition, \DateTimeImmutable $dateTime): ?ViewPeriod
    {
        $query = $this->createQueryBuilder('vp')
            ->where('vp.startDateTime <= :gameStart')
            ->andWhere('vp.endDateTime >= :gameStart')
            ->andWhere('vp.sourceCompetition = :competition')
            ;
        $query = $query->setParameter('gameStart', $dateTime);
        $query = $query->setParameter('competition', $competition);

        /** @var list<ViewPeriod> $viewPeriods */
        $viewPeriods = $query->getQuery()->getResult();
        $viewPeriod = reset($viewPeriods);
        return $viewPeriod === false ? null : $viewPeriod;
    }

    public function findOneByGameRoundNumber(Competition $competition, int $gameRoundNumber): ?ViewPeriod
    {
        $exprExists = $this->_em->getExpressionBuilder();

        $query = $this->createQueryBuilder('vp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere(
                $exprExists->exists(
                    $this->_em->createQueryBuilder()
                        ->select('gr.id')
                        ->from('SuperElf\GameRound', 'gr')
                        ->where('gr.viewPeriod = vp.id')
                        ->andWhere('gr.number = :gameRoundNumber')
                        ->getDQL()
                )
            )
        ;
        $query = $query->setParameter('competition', $competition);
        $query = $query->setParameter('gameRoundNumber', $gameRoundNumber);

        /** @var list<ViewPeriod> $viewPeriods */
        $viewPeriods = $query->getQuery()->getResult();
        if (count($viewPeriods) === 0) {
            return null;
        }
        return reset($viewPeriods);
    }

//    select * from viewPeriods vp
//    where (select count(*) from games where startDateTime > vp.startDateTime and startDateTime < vp.endDateTime and resourceBatch = 1 )  > 4.5
    public function findGameRoundOwner(Poule $poule, AgainstSportVariant $sportVariant, int $gameRoundNumber): ?ViewPeriod
    {
        $nrOfGamesPerRound = $sportVariant->getNrOfGamesOneGameRound($poule->getPlaces()->count());
        $halfNrOfGamesPerRound = (int)floor($nrOfGamesPerRound / 2);

        $exprCount = $this->_em->getExpressionBuilder();

        $gamesQb = $this->_em->createQueryBuilder()
            ->select($exprCount->count('g.id'))
            ->from('Sports\Game', 'g')
            ->where('g.startDateTime >= vp.startDateTime')
            ->andWhere('g.startDateTime <= vp.endDateTime')
            ->andWhere('g.batchNr = :gameRoundNumber');

        $qb = $this->createQueryBuilder('vp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere(
                "(" .
                    $gamesQb->getDQL()
                . ") > " . $halfNrOfGamesPerRound
            );

        $qb = $qb->setParameter('gameRoundNumber', $gameRoundNumber);
        $qb = $qb->setParameter('competition', $poule->getRound()->getNumber()->getCompetition());

        /** @var list<ViewPeriod> $viewPeriods */
        $viewPeriods = $qb->getQuery()->getResult();
        if (count($viewPeriods) === 0) {
            return null;
        }
        return reset($viewPeriods);
    }
}
