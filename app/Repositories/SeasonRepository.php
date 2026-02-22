<?php

declare(strict_types=1);

namespace App\Repositories;

use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Sports\Season;
use SuperElf\CompetitionConfig;

/**
 * @template-extends EntityRepository<Season>
 */
final class SeasonRepository extends EntityRepository
{
    public function findOneByPeriod(Period $period): Season|null
    {
        $query = $this->createQueryBuilder('s')
            ->where('s.startDateTime < :end')
            ->andWhere('s.endDateTime > :start');

        $query = $query->setParameter('end', $period->endDate);
        $query = $query->setParameter('start', $period->startDate);

        /** @var list<Season> $seasons */
        $seasons = $query->getQuery()->getResult();
        $season = reset($seasons);
        return $season !== false ? $season : null;
    }

    /**
     * @return list<Season>
     */
    public function findExtWithCompetitionConfigs(): array
    {
        $query = $this->createQueryBuilder('s')
            ->where(
                $this->getEntityManager()->getExpressionBuilder()->exists(
                    $this->getEntityManager()->createQueryBuilder()
                        ->select('cc.id')
                        ->from(CompetitionConfig::class, 'cc')
                        ->join("cc.sourceCompetition", "sc")
                        ->where('sc.season = s')
                        ->getDQL()
                )
            )
        ;

        /** @var list<Season> $seasons */
        $seasons = $query->getQuery()->getResult();
        return $seasons;
    }
}
