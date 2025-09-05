<?php

declare(strict_types=1);

namespace SuperElf\Season;

use Doctrine\ORM\EntityRepository;
use Sports\Season;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<Season>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Season>
     */
    use BaseRepository;

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
                        ->from('SuperElf\CompetitionConfig', 'cc')
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
