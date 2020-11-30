<?php

declare(strict_types=1);

namespace SuperElf\Period\View;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use League\Period\Period;
use Sports\Competitor;
use Sports\Game;
use SuperElf\Period\View as ViewPeriod;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?ViewPeriod
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    public function findOneByGame(Game $game): ?ViewPeriod
    {
        $query = $this->createQueryBuilder('vp')
            ->where('vp.startDateTime <= :gameStart')
            ->andWhere('vp.endDateTime >= :gameStart')
            ->andWhere('vp.sourceCompetition = :competition')
            ;
        $query = $query->setParameter('gameStart', $game->getStartDateTime() );
        $query = $query->setParameter('competition', $game->getRound()->getNumber()->getCompetition() );
        $games = $query->getQuery()->getResult();
        if (count($games) === 0) {
            return null;
        }
        return reset($games);
    }
}
