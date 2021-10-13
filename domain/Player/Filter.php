<?php
declare(strict_types=1);

namespace SuperElf\Period\View\Person;

use DateTimeImmutable;

class Filter
{
    // protected DateTimeImmutable|null $end = null;

    public function __construct(
        protected int $viewPeriodId,
        protected int|string|null $teamId = null,
        protected int|null $line = null)
    {
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
