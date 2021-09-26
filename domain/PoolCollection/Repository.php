<?php
declare(strict_types=1);

namespace SuperElf\PoolCollection;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\PoolCollection;

/**
 * @template-extends EntityRepository<PoolCollection>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<PoolCollection>
     */
    use BaseRepository;
}

