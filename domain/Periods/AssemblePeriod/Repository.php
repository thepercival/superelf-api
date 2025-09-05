<?php

declare(strict_types=1);

namespace SuperElf\Periods\AssemblePeriod;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Periods\AssemblePeriod as AssemblePeriod;

/**
 * @template-extends EntityRepository<AssemblePeriod>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<AssemblePeriod>
     */
    use BaseRepository;
}
