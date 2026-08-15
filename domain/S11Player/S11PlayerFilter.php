<?php

declare(strict_types=1);

namespace SuperElf\S11Player;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class S11PlayerFilter
{
    protected int|string|null $teamId = null;

    public function __construct(
        protected int $viewPeriodId,
        int|string|null $teamId,
        protected int|null $line = null,
        protected int|null $maxResults = 50
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

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }
}
