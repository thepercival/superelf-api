<?php
declare(strict_types=1);

namespace SuperElf\Substitute\Participation;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Substitute\Participation;

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