<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Formation;

/**
 * @template-extends EntityRepository<Formation>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Formation>
     */
    use BaseRepository;
}
