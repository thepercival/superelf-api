<?php

declare(strict_types=1);

namespace SuperElf\Game\Against;

use Sports\Team\Player as TeamPlayer;
use SuperElf\FootballScore;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class GoalEvent extends Event
{
    public function __construct(
        TeamPlayer $player,
        int $minute,
        protected FootballScore $score,
        protected TeamPlayer|null $assistPlayer)
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

    public function getScoreNative(): string
    {
        return $this->score->value;
    }

    public function getAssistPlayer(): TeamPlayer|null
    {
        return $this->assistPlayer;
    }
}
