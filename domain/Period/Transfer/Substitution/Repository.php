<?php

declare(strict_types=1);

namespace SuperElf\Period\Transfer\Substitution;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Period\Transfer\Substitution;

/**
 * @template-extends EntityRepository<Substitution>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Substitution>
     */
    use BaseRepository;
}
