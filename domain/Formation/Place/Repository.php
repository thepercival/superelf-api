<?php
declare(strict_types=1);

namespace SuperElf\Formation\Place;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Formation\Place as FormationPlayer;

/**
 * @template-extends EntityRepository<FormationPlayer>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<FormationPlayer>
     */
    use BaseRepository;
}
