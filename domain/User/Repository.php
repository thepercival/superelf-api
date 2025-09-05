<?php

declare(strict_types=1);

namespace SuperElf\User;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\User;

/**
 * @template-extends EntityRepository<User>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<User>
     */
    use BaseRepository;
}
