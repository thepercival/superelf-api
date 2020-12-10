<?php

namespace SuperElf\Period\View\Person;

use DateTimeImmutable;

class Filter
{
    protected int $viewPeriodId;
    protected DateTimeImmutable $end;
    /**
     * @var int|string|null
     */
    protected $teamId;
    /**
     * @var int|null
     */
    protected $line;

    /**
     * @param int $viewPeriodId
     * @param int|string|null $teamId
     * @param int|null $line
     */
    public function __construct(int $viewPeriodId, $teamId = null, int $line = null)
    {
        $this->viewPeriodId = $viewPeriodId;
        $this->teamId = $teamId;
        $this->line = $line;
    }

    public function getViewPeriodId(): int
    {
        return $this->viewPeriodId;
    }

    /**
     * @return int|string|null
     */
    public function getTeamId()
    {
        return $this->teamId;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }
}
