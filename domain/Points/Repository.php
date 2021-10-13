<?php
declare(strict_types=1);

namespace SuperElf\Season\Points;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Formation;

/**
 * @template-extends EntityRepository<Points>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Points>
     */
    use BaseRepository;
}
