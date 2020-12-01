<?php

declare(strict_types=1);

namespace SuperElf\GameRound;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
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

    public function findOneByGame(Game $game): ?BaseGameRound
    {
        $query = $this->createQueryBuilder('gr')
            ->join('gr.viewPeriod', 'vp')
            ->where('vp.sourceCompetition = :competition')
            ->andWhere('gr.number = :gameRound')
        ;
        $query = $query->setParameter('competition', $game->getRound()->getNumber()->getCompetition() );
        $query = $query->setParameter('gameRound', $game->getBatchNr() );

        $gameRounds = $query->getQuery()->getResult();
        if (count($gameRounds) === 0) {
            return null;
        }
        return reset($gameRounds);
    }
}
