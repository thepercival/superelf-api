<?php
declare(strict_types=1);

namespace SuperElf\User;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use SuperElf\User;

/**
 * @template-extends EntityRepository<User>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<User>
     */
    use BaseRepository;
}
