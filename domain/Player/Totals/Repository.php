<?php

declare(strict_types=1);

namespace SuperElf\Player\Totals;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Player\Totals as S11PlayerTotals;

/**
 * @template-extends EntityRepository<S11PlayerTotals>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<S11PlayerTotals>
     */
    use BaseRepository;
}
