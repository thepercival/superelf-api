<?php

declare(strict_types=1);

namespace SuperElf\Periods\TransferPeriod;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Periods\TransferPeriod as TransferPeriod;

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
