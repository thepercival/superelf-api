<?php
declare(strict_types=1);

namespace SuperElf\Player\Totals;

use Doctrine\ORM\EntityRepository;
use SuperElf\Player\Totals as S11PlayerTotals;
use SportsHelpers\Repository as BaseRepository;

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
