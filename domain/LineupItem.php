<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Team\Player as TeamPlayer;
use SuperElf\LineupItem\Substitute;

class LineupItem
{
    public function __construct(protected TeamPlayer $player, protected Substitute|null $substitute)
    {
    }

    public function getPlayer(): TeamPlayer
    {
        return $this->player;
    }

    public function getSubstitute(): Substitute|null
    {
        return $this->substitute;
    }
}
