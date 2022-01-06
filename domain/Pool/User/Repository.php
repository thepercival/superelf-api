<?php

declare(strict_types=1);

namespace SuperElf\Pool\User;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Pool\User as PoolUser;

/**
 * @template-extends EntityRepository<PoolUser>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<PoolUser>
     */
    use BaseRepository;
}
