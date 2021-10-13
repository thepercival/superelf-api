<?php
declare(strict_types=1);

namespace SuperElf\Substitute\Appearance;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Substitute\Appearance;

/**
 * @template-extends EntityRepository<Appearance>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Appearance>
     */
    use BaseRepository;
}