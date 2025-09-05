<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use League\Period\Period;
use Sports\Season\Repository as SeasonRepository;
use Sports\Season;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\CompetitionConfig;

/**
 * @template-extends EntityRepository<CompetitionConfig>
 */
final class Repository extends EntityRepository
{
    private SeasonRepository $seasonRepos;

    /**
     * @use BaseRepository<CompetitionConfig>
     */
    use BaseRepository;

    /**
     * @param EntityManagerInterface $em
     * @param ClassMetadata<CompetitionConfig> $class
     */
    public function __construct(
        EntityManagerInterface $em,
        ClassMetadata $class
    ) {
        parent::__construct($em, $class);
        $this->seasonRepos = new SeasonRepository($em, $em->getClassMetadata(Season::class));
    }

    /**
     * @return list<CompetitionConfig>
     */
    public function findActive(): array
    {
        $queryBuilder = $this->createQueryBuilder('cc')
            ->join("cc.sourceCompetition", "c")
            ->join("cc.createAndJoinPeriod", "candj")
            ->where('candj.startDateTime < :currentDateTime')
            ->andWhere('candj.endDateTime > :currentDateTime')
            ->setParameter('currentDateTime', new \DateTimeImmutable());

        /** @var list<CompetitionConfig> $competitionConfigs */
        $competitionConfigs = $queryBuilder->getQuery()->getResult();
        return $competitionConfigs;
    }



    public function findCurrentSeason(): Season
    {
        $period = new Period(new \DateTimeImmutable(), (new \DateTimeImmutable())->add(new \DateInterval('PT1S')));
        $season = $this->seasonRepos->findOneByPeriod($period);
        if( $season === null ) {
            throw new \Exception('no current season could be found');
        }
        return $season;
    }

    /**
     * @return list<CompetitionConfig>
     */
    public function findBySeason(Season $season): array
    {
        $queryBuilder = $this->createQueryBuilder('cc')
            ->join("cc.sourceCompetition", "c")
            ->where('c.season = :season')
            ->setParameter('season', $season);

        /** @var list<CompetitionConfig> $competitionConfigs */
        $competitionConfigs = $queryBuilder->getQuery()->getResult();
        return $competitionConfigs;
    }
}
