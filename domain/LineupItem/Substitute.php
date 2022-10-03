<?php

namespace SuperElf\LineupItem;

use Sports\Team\Player as TeamPlayer;
use SuperElf\LineupItem;

class Substitute extends LineupItem
{
    public function __construct(protected int $minute, TeamPlayer $player, self|null $substitute)
    {
        parent::__construct($player, $substitute);
    }

    public function getMinute(): int
    {
        return $this->minute;
    }
}
