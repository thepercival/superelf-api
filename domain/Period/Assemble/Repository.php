<?php
declare(strict_types=1);

namespace SuperElf\Period\Assemble;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Period\Assemble as AssemblePeriod;

/**
 * @template-extends EntityRepository<AssemblePeriod>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<AssemblePeriod>
     */
    use BaseRepository;
}