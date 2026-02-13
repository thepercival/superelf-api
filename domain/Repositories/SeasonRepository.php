<?php

declare(strict_types=1);

namespace SuperElf\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Season;
use SuperElf\CompetitionConfig;

/**
 * @template-extends EntityRepository<Season>
 */
final class SeasonRepository extends EntityRepository
{
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
