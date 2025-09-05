<?php

declare(strict_types=1);

namespace SuperElf\Player;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Filter
{
    protected int|string|null $teamId = null;

    public function __construct(
        protected int $viewPeriodId,
        int|string|null $teamId,
        protected int|null $line = null
    ) {
        $this->teamId = $teamId;
    }

    public function getViewPeriodId(): int
    {
        return $this->viewPeriodId;
    }

    public function getTeamId(): int|string|null
    {
        return $this->teamId;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }
}
