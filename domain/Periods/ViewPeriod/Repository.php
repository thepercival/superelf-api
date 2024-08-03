<?php

declare(strict_types=1);

namespace SuperElf\Periods\ViewPeriod;

use Doctrine\ORM\EntityRepository;
use Sports\Competition;
use Sports\Poule;
use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SuperElf\Periods\ViewPeriod as ViewPeriod;

/**
 * @template-extends EntityRepository<ViewPeriod>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<ViewPeriod>
     */
    use BaseRepository;



    public function findOneByGameRoundNumber(Competition $competition, int $gameRoundNumber): ?ViewPeriod
    {
        $exprExists = $this->getEntityManager()->getExpressionBuilder();

        $query = $this->createQueryBuilder('vp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere(
                $exprExists->exists(
                    $this->getEntityManager()->createQueryBuilder()
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
    public function findGameRoundOwner(Poule $poule, AgainstH2h $sportVariant, int $gameRoundNumber): ?ViewPeriod
    {
        $variantWithNrOfPlaces = new AgainstH2hWithNrOfPlaces(count($poule->getPlaces()), $sportVariant);
        $halfNrOfGamesPerRound = (int)floor($variantWithNrOfPlaces->getNrOfGamesSimultaneously() / 2);

        $exprCount = $this->getEntityManager()->getExpressionBuilder();

        $gamesQb = $this->getEntityManager()->createQueryBuilder()
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
