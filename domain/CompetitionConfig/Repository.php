<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig;

use Doctrine\ORM\EntityRepository;
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
}
