<?php
declare(strict_types=1);

namespace SuperElf\Pool\User\ViewPeriodPerson;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;

/**
 * @template-extends EntityRepository<PoolUserViewPeriodPerson>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<PoolUserViewPeriodPerson>
     */
    use BaseRepository;
}