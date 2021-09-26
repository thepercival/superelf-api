<?php
declare(strict_types=1);

namespace SuperElf\ScoutedPerson;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\ScoutedPerson;

/**
 * @template-extends EntityRepository<ScoutedPerson>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<ScoutedPerson>
     */
    use BaseRepository;
}