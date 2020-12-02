<?php

declare(strict_types=1);

namespace SuperElf\GameRound;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sports\Competition;
use Sports\Game;
use SuperElf\GameRound as BaseGameRound;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?BaseGameRound
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

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
