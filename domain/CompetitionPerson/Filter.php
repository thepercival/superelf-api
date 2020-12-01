<?php

namespace SuperElf\CompetitionPerson;

use DateTimeImmutable;
use League\Period\Period;

class Filter
{
    /**
     * @var int|string
     */
    protected $sourceCompetitionId;
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
     * @param string|int $sourceCompetitionId
     * @param int|string|null $teamId
     * @param int|null $line
     */
    public function __construct($sourceCompetitionId, $teamId = null, int $line = null)
    {
        $this->sourceCompetitionId = $sourceCompetitionId;
        $this->teamId = $teamId;
        $this->line = $line;
    }

    /**
     * @return int|string
     */
    public function getSourceCompetitionId()
    {
        return $this->sourceCompetitionId;
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
