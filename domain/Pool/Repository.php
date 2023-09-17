<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use SuperElf\CompetitionConfig;
use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Pool;
use SuperElf\PoolCollection;
use SuperElf\Role;
use SuperElf\User;

/**
 * @template-extends EntityRepository<Pool>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Pool>
     */
    use BaseRepository;


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

        $query = $query->andWhere('a.name = ' . PoolCollection::S11Association);

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
        string $name = null,
        DateTimeImmutable $startDateTime = null,
        DateTimeImmutable $endDateTime = null
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
            ->from('SuperElf\Pool\User', 'pu')
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
