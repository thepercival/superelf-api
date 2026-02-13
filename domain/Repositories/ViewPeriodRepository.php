<?php

declare(strict_types=1);

namespace SuperElf\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Sports\Competition;
use SuperElf\GameRound;
use SuperElf\GameRound\GameRoundShell;
use SuperElf\Period;
use League\Period\Period as LeaguePeriod;
use SuperElf\Periods\ViewPeriod as ViewPeriod;

/**
 * @psalm-type _GameRoundRow = array{gameRoundNumber: int, startDateTime: string, endDateTime: string, created: int, inProgress: int, finished: int}
 * @template-extends EntityRepository<ViewPeriod>
 */
final class ViewPeriodRepository extends EntityRepository
{
    public function findOneByGameRoundNumber(Competition $competition, int $gameRoundNumber): ?ViewPeriod
    {
        $exprExists = $this->getEntityManager()->getExpressionBuilder();

        $query = $this->createQueryBuilder('vp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere(
                $exprExists->exists(
                    $this->getEntityManager()->createQueryBuilder()
                        ->select('gr.id')
                        ->from(GameRound::class, 'gr')
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
//    public function findGameRoundOwner(Poule $poule, AgainstH2h $sportVariant, int $gameRoundNumber): ?ViewPeriod
//    {
//        $variantWithNrOfPlaces = new AgainstH2hWithNrOfPlaces(count($poule->getPlaces()), $sportVariant);
//        $halfNrOfGamesPerRound = (int)floor($variantWithNrOfPlaces->getNrOfGamesSimultaneously() / 2);
//
//        $exprCount = $this->getEntityManager()->getExpressionBuilder();
//
//        $gamesQb = $this->getEntityManager()->createQueryBuilder()
//            ->select($exprCount->count('g.id'))
//            ->from(Game::class, 'g')
//            ->where('g.startDateTime >= vp.startDateTime')
//            ->andWhere('g.startDateTime <= vp.endDateTime')
//            ->andWhere('g.batchNr = :gameRoundNumber');
//
//        $qb = $this->createQueryBuilder('vp')
//            ->where('vp.sourceCompetition = :competition')
//            ->andWhere(
//                "(" .
//                    $gamesQb->getDQL()
//                . ") > " . $halfNrOfGamesPerRound
//            );
//
//        $qb = $qb->setParameter('gameRoundNumber', $gameRoundNumber);
//        $qb = $qb->setParameter('competition', $poule->getRound()->getNumber()->getCompetition());
//        /** @var list<ViewPeriod> $viewPeriods */
//        $viewPeriods = $qb->getQuery()->getResult();
//        if (count($viewPeriods) === 0) {
//            return null;
//        }
//        return reset($viewPeriods);
//    }

    /**
     * @param Competition $sourceCompetition
     * @param ViewPeriod $viewPeriod
     * @return list<GameRoundShell>
     * @throws \Exception
     */
    public function findGameRoundShells(
        Competition $sourceCompetition,
        ViewPeriod $viewPeriod,
        bool $orderByDate = false,
    ): array
    {

        // Define the ResultSetMapping
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('gameRoundNumber', 'gameRoundNumber');
        $rsm->addScalarResult('startDateTime', 'startDateTime');
        $rsm->addScalarResult('endDateTime', 'endDateTime');
        $rsm->addScalarResult('created', 'created');
        $rsm->addScalarResult('inProgress', 'inProgress');
        $rsm->addScalarResult('finished', 'finished');

        // Create the native SQL query
        $sql = "
        select 		min(ag.gameRoundNumber) as gameRoundNumber
        ,           min(ag.startDateTime) as startDateTime
        ,           max(ag.startDateTime) as endDateTime
        ,			COUNT(CASE WHEN ag.state = 'created' THEN 1 END) AS created
        ,			COUNT(CASE WHEN ag.state = 'inProgress' THEN 1 END) AS inProgress
        ,			COUNT(CASE WHEN ag.state = 'finished' THEN 1 END) AS finished
        from 		againstGames as ag
                    join poules p on p.id = ag.pouleId
    			    join rounds r on r.id = p.roundId
    			    join structureCells sc on sc.id = r.structureCellId
    			    join roundNumbers rn on rn.id = sc.roundNumberId
        where 		ag.startDateTime >= :viewPeriodStart
        and 		ag.startDateTime <= :viewPeriodEnd
        and         rn.competitionId = :sourceCompetitionId
        group by 	ag.gameRoundNumber
        order by 	ag." . ($orderByDate ? "startDateTime" : "gameRoundNumber") . "
       ";

        // Create the query
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('viewPeriodStart', $viewPeriod->getStartDateTime());
        $query->setParameter('viewPeriodEnd', $viewPeriod->getEndDateTime());
        $query->setParameter('sourceCompetitionId', $sourceCompetition->getId());

        /** @var list<_GameRoundRow> $results */
        $results = $query->getResult();


        return array_map(function($row): GameRoundShell {
            return new GameRoundShell(
                $row['gameRoundNumber'],
                new Period(LeaguePeriod::fromDate($row['startDateTime'], $row['endDateTime'])),
                $row['created'],
                $row['inProgress'],
                $row['finished']
            );
        }, $results );
    }
}
