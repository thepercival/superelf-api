<?php

namespace SuperElf;

use DateTimeImmutable;
use League\Period\Period;

class ActiveConfig
{
    protected DateTimeImmutable $createAndJoinStart;
    protected DateTimeImmutable $createAndJoinEnd;
    protected array $sourceCompetitions;
    /**
     * @var array | Formation[]
     */
    protected array $availableFormations = [];

    public function __construct(
        Period $createAndJoinPeriod,
        array $sourceCompetitions )
    {
        $this->createAndJoinStart = $createAndJoinPeriod->getStartDate();
        $this->createAndJoinEnd = $createAndJoinPeriod->getEndDate();

        $this->sourceCompetitions = $sourceCompetitions;
    }

    public function getCreateAndJoinPeriod(): Period
    {
        return new Period( $this->createAndJoinStart, $this->createAndJoinEnd );
    }

    public function getSourceCompetitions(): array
    {
        return $this->sourceCompetitions;
    }

    /**
     * @return array|Formation[]
     */
    public function getAvailableFormations(): array
    {
        return $this->availableFormations;
    }


    public function setAvailableFormations( array $availableFormations )
    {
        $this->availableFormations = $availableFormations;
    }
}
