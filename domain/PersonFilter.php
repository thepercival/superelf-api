<?php

namespace SuperElf;

use DateTimeImmutable;
use League\Period\Period;

class PersonFilter
{
    protected DateTimeImmutable $startDateTime;
    protected DateTimeImmutable $endDateTime;
    /**
     * @var int|string|null
     */
    protected $teamId;
    /**
     * @var int|null
     */
    protected $line;

    /**
     * PersonFilter constructor.
     * @param Period $period
     * @param int|string|null $teamId
     * @param int|null $line
     */
    public function __construct(Period $period, $teamId = null, int $line = null)
    {
        $this->startDateTime = $period->getStartDate();
        $this->endDateTime = $period->getEndDate();
        $this->teamId = $teamId;
        $this->line = $line;
    }

    public function getPeriod(): Period
    {
        return new Period( $this->startDateTime, $this->endDateTime );
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
