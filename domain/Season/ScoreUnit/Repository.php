<?php
declare(strict_types=1);

namespace SuperElf\Season\ScoreUnit;

use SuperElf\Season\ScoreUnit as BaseScoreUnit;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<BaseScoreUnit>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<BaseScoreUnit>
     */
    use BaseRepository;
}
