<?php

declare(strict_types=1);

namespace SuperElf\Replacement;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Replacement as ReplacementBase;

/**
 * @template-extends EntityRepository<ReplacementBase>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<ReplacementBase>
     */
    use BaseRepository;
}
