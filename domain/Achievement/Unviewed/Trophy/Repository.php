<?php

declare(strict_types=1);

namespace SuperElf\Achievement\Unviewed\Trophy;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Achievement\Unviewed\Trophy as UnviewedTrophy;

/**
 * @template-extends EntityRepository<UnviewedTrophy>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<UnviewedTrophy>
     */
    use BaseRepository;
}
