<?php

declare(strict_types=1);

namespace SuperElf\Transfer;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Transfer;

/**
 * @template-extends EntityRepository<Transfer>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Transfer>
     */
    use BaseRepository;
}
