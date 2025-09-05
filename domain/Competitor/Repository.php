<?php

declare(strict_types=1);

namespace SuperElf\Competitor;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Competitor;

/**
 * @template-extends EntityRepository<Competitor>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Competitor>
     */
    use BaseRepository;
}
