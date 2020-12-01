<?php

declare(strict_types=1);

namespace SuperElf\CompetitionPerson\GameRoundScore;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sports\Competition;
use Sports\Person;
use SuperElf\CompetitionPerson\GameRoundScore as BaseGameRoundScore;

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
}
