<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig;

use Doctrine\ORM\EntityRepository;
use Sports\Season;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\CompetitionConfig;

/**
 * @template-extends EntityRepository<CompetitionConfig>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<CompetitionConfig>
     */
    use BaseRepository;

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
