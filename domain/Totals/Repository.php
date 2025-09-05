<?php

declare(strict_types=1);

namespace SuperElf\Totals;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Totals as S11Totals;

/**
 * @template-extends EntityRepository<S11Totals>
 */
final class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<S11Totals>
     */
    use BaseRepository;
}
