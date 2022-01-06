<?php

declare(strict_types=1);

namespace SuperElf\Period\Transfer;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Period\Transfer as TransferPeriod;

/**
 * @template-extends EntityRepository<TransferPeriod>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<TransferPeriod>
     */
    use BaseRepository;
}
