<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use SuperElf\Pool;
use SuperElf\Role;
use SuperElf\User;

class Repository extends \SportsHelpers\Repository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?Pool
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

//    public function customPersist(Tournament $tournament, bool $flush)
//    {
//        $leagueRepos = new LeagueRepository($this->_em, $this->_em->getClassMetadata(League::class));
//        $leagueRepos->save($tournament->getCompetition()->getLeague());
//        $competitionRepos = new CompetitionRepository($this->_em, $this->_em->getClassMetadata(Competition::class));
//        $competitionRepos->customPersist($tournament->getCompetition());
//        $this->_em->persist($tournament);
//        if ($flush) {
//            $this->_em->flush();
//        }
//    }

    public function findByFilter(
        string $name = null,
        DateTimeImmutable $startDateTime = null,
        DateTimeImmutable $endDateTime = null
    ) {
        $query = $this->createQueryBuilder('p')
            ->join("p.collection", "pc")
            ->join("p.season", "s");

        if ($startDateTime !== null) {
            $query = $query->where('s.startDateTime >= :startDateTime');
            $query = $query->setParameter('startDateTime', $startDateTime);
        }

        if ($endDateTime !== null) {
            $query = $query->andWhere('s.endDateTime <= :endDateTime');
            $query = $query->setParameter('endDateTime', $endDateTime);
        }

        if ($name !== null) {
            if ($startDateTime !== null || $endDateTime !== null) {
                $query = $query->andWhere("pc.name like :name");
            } else {
                $query = $query->where('pc.name like :name');
            }
            $query = $query->setParameter('name', '%' . $name . '%');
        }

        return $query->getQuery()->getResult();
    }

    public function findByRoles(User $user, int $roles)
    {
        $exprExists = $this->getEM()->getExpressionBuilder();

        $competitorQb = $this->getEM()->createQueryBuilder()
            ->select('c.id')
            ->from('SuperElf\Competitor', 'c')
            ->where('c.pool = p')
            ->andWhere('c.user = :user');
        if( ( $roles & Role::ADMIN ) === Role::ADMIN ) {
            $competitorQb = $competitorQb->andWhere('c.admin = :admin');
        }

        $qb = $this->createQueryBuilder('p')
        ->andWhere(
            $exprExists->exists(
                $competitorQb->getDQL()
            )
        );
        $qb = $qb->setParameter('user', $user);
        if( ( $roles & Role::ADMIN ) === Role::ADMIN ) {
            $qb = $qb->setParameter('admin', true);
        }

        return $qb->getQuery()->getResult();
    }

//
//    public function remove($tournament)
//    {
//        $leagueRepos = new LeagueRepository($this->_em, $this->_em->getClassMetadata(League::class));
//        return $leagueRepos->remove($tournament->getCompetition()->getLeague());
//    }
}
