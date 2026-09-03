<?php

declare(strict_types=1);

namespace SuperElf\S11Player;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class S11PlayerFilter
{
    /**
     * @param list<int|string>|null $teamIds
     */
    public function __construct(
        protected int $viewPeriodId,
        protected array|null $teamIds = null,
        protected int|null $line = null,
        protected int|null $maxResults = 50,
        protected bool $orderByPoints = false
    ) {}

    public function getViewPeriodId(): int
    {
        return $this->viewPeriodId;
    }

    /**
     * @return list<int|string>|null
     */
    public function getTeamIds(): array|null
    {
        return $this->teamIds;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function getOrderByPoints(): bool
    {
        return $this->orderByPoints;
    }
}
