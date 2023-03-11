<?php

declare(strict_types=1);

namespace SuperElf\Achievement\Unviewed\Badge;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Achievement\Unviewed\Badge as UnviewedBadge;

/**
 * @template-extends EntityRepository<UnviewedBadge>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<UnviewedBadge>
     */
    use BaseRepository;
}
