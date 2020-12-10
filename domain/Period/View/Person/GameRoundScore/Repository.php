<?php

declare(strict_types=1);

namespace SuperElf\Period\View\Person\GameRoundScore;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sports\Competition;
use Sports\Person;
use Sports\Team;
use SuperElf\Period\View\Person\GameRoundScore as BaseGameRoundScore;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?BaseGameRoundScore
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function findOneByCustom(Competition $competition, Person $person, int $gameRound): ?BaseGameRoundScore
    {
        $query = $this->createQueryBuilder('grs')
            ->join('grs.gameRound', 'gr')
            ->join('gr.viewPeriod', 'vp')
            ->join('grs.competitionPerson', 'cp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere('cp.person = :person')
            ->andWhere('cp.sourceCompetition = :competition')
            ->andWhere('gr.number = :gameRound')
        ;
        $query = $query->setParameter('competition', $competition );
        $query = $query->setParameter('person', $person );
        $query = $query->setParameter('gameRound', $gameRound );

        $gameRoundScores = $query->getQuery()->getResult();
        if (count($gameRoundScores) === 0) {
            return null;
        }
        return reset($gameRoundScores);
    }

    /**
     * @param Competition $competition
     * @param Team $team
     * @param int $gameRoundNumber
     * @param DateTimeImmutable $dateTime
     * @return array|BaseGameRoundScore[]
     */
    public function findByCustom(Competition $competition, Team $team, int $gameRoundNumber, DateTimeImmutable $dateTime)
    {
        // haal alle gameroundscores op voor alle spelers van team
        $exprExists = $this->getEM()->getExpressionBuilder();

        $query = $this->createQueryBuilder('grs')
            ->join('grs.gameRound', 'gr')
            ->join('gr.viewPeriod', 'vp')
            ->join('grs.viewPeriodPerson', 'vpp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere('gr.number = :gameRoundNumber')
            ->andWhere(
                $exprExists->exists(
                    $this->getEM()->createQueryBuilder()
                        ->select('gr.id')
                        ->from('Sports\Team\Player', 'pl')
                        ->where('pl.team = :team')
                        ->andWhere('pl.startDateTime <= :dateTime')
                        ->andWhere('pl.endDateTime >= :dateTime')
                        ->getDQL()
                )
            )
        ;
        $query = $query->setParameter('competition', $competition );
        $query = $query->setParameter('gameRoundNumber', $gameRoundNumber );
        $query = $query->setParameter('team', $team );
        $query = $query->setParameter('dateTime', $dateTime );

        return $query->getQuery()->getResult();
    }
}
