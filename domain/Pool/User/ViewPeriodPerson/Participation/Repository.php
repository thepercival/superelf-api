<?php
declare(strict_types=1);

namespace SuperElf\Pool\User\ViewPeriodPerson\Participation;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Pool\User\ViewPeriodPerson\Participation;

/**
 * @template-extends EntityRepository<Participation>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Participation>
     */
    use BaseRepository;
}