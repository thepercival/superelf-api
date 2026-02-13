<?php

declare(strict_types=1);

namespace SuperElf\Repositories;

use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use SuperElf\CompetitionConfig;
use SuperElf\League as S11League;
use SuperElf\Pool;
use SuperElf\Role;
use SuperElf\Pool\User as PoolUser;
use SuperElf\User;

/**
 * @template-extends EntityRepository<Pool>
 */
final class PoolRepository extends EntityRepository
{

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


    public function findWorldCup(CompetitionConfig $competitionConfig): Pool|null {
        $query = $this->createQueryBuilder('p')
            ->join("p.collection", "pc")
            ->join("pc.association", "a")
            ->join("p.competitionConfig", "cc")
        ;

        $query = $query->where('p.competitionConfig = :competitionConfig');
        $query = $query->setParameter('competitionConfig', $competitionConfig);

        $query = $query->andWhere('a.name = :associationName');
        $query = $query->setParameter('associationName', S11League::WorldCup->name);

        /** @var Pool|null $worldCupPool */
        $worldCupPool = $query->getQuery()->getOneOrNullResult();
        return $worldCupPool;
    }

    /**
     * @param string|null $name
     * @param DateTimeImmutable|null $startDateTime
     * @param DateTimeImmutable|null $endDateTime
     * @return list<Pool>
     */
    public function findByFilter(
        string|null $name = null,
        DateTimeImmutable|null $startDateTime = null,
        DateTimeImmutable|null $endDateTime = null
    ): array {
        $query = $this->createQueryBuilder('p')
            ->join("p.collection", "pc")
            ->join("pc.association", "a")
            ->join("p.competitionConfig", "cc")
            ->join("cc.sourceCompetition", "sc")
            ->join("sc.season", "s")
        ;

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
                $query = $query->andWhere("a.name like :name");
            } else {
                $query = $query->where('a.name like :name');
            }
            $query = $query->setParameter('name', '%' . $name . '%');
        }
        /** @var list<Pool> $pools */
        $pools = $query->getQuery()->getResult();
        return $pools;
    }

    /**
     * @param User $user
     * @param int $roles
     * @return list<Pool>
     */
    public function findByRoles(User $user, int $roles): array
    {
        $exprExists = $this->getEntityManager()->getExpressionBuilder();

        $competitorQb = $this->getEntityManager()->createQueryBuilder()
            ->select('pu.id')
            ->from(PoolUser::class, 'pu')
            ->where('pu.pool = p')
            ->andWhere('pu.user = :user');
        if (($roles & Role::ADMIN) === Role::ADMIN) {
            $competitorQb = $competitorQb->andWhere('pu.admin = :admin');
        }

        $qb = $this->createQueryBuilder('p')
        ->andWhere(
            $exprExists->exists(
                $competitorQb->getDQL()
            )
        );
        $qb = $qb->setParameter('user', $user);
        if (($roles & Role::ADMIN) === Role::ADMIN) {
            $qb = $qb->setParameter('admin', true);
        }
        /** @var list<Pool> $pools */
        $pools = $qb->getQuery()->getResult();
        // $x = $qb->getQuery()->getSQL();
        return $pools;
    }

//
//    public function remove($tournament)
//    {
//        $leagueRepos = new LeagueRepository($this->_em, $this->_em->getClassMetadata(League::class));
//        return $leagueRepos->remove($tournament->getCompetition()->getLeague());
//    }
}
