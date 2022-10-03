<?php

declare(strict_types=1);

namespace SuperElf\Game\Against;

use Sports\Team\Player as TeamPlayer;

class Event
{
    public function __construct(protected TeamPlayer $player, protected int $minute)
    {
    }

    public function getPlayer(): TeamPlayer
    {
        return $this->player;
    }

    public function getMinute(): int
    {
        return $this->minute;
    }
}
