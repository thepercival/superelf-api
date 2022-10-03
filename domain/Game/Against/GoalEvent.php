<?php

declare(strict_types=1);

namespace SuperElf\Game\Against;

use Sports\Team\Player as TeamPlayer;
use SuperElf\FootballScore;

class GoalEvent extends Event
{
    public function __construct(
        TeamPlayer $player,
        int $minute,
        protected FootballScore $score,
        protected TeamPlayer|null $substitute)
    {
        parent::__construct($player, $minute);
    }

    public function getPlayer(): TeamPlayer
    {
        return $this->player;
    }

    public function getMinute(): int
    {
        return $this->minute;
    }

    public function getScoreNative(): string
    {
        return $this->score->value;
    }

    public function getSubstitute(): TeamPlayer|null
    {
        return $this->substitute;
    }
}
