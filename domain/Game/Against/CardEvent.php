<?php

declare(strict_types=1);

namespace SuperElf\Game\Against;

use Sports\Team\Player as TeamPlayer;
use SuperElf\FootballScore;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class CardEvent extends Event
{
    public function __construct(
        TeamPlayer $player,
        int $minute,
        protected FootballScore $color)
    {
        parent::__construct($player, $minute);
    }

    #[\Override]
    public function getPlayer(): TeamPlayer
    {
        return $this->player;
    }

    #[\Override]
    public function getMinute(): int
    {
        return $this->minute;
    }

    public function getColorNative(): string
    {
        return $this->color->value;
    }
}
