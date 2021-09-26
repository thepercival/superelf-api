<?php
declare(strict_types=1);

namespace SuperElf;

use DateTimeImmutable;
use League\Period\Period;

class ActiveConfig
{
    protected DateTimeImmutable $createAndJoinStart;
    protected DateTimeImmutable $createAndJoinEnd;
    protected array $sourceCompetitions;
    /**
     * @var list<array<string, string|array<int,int>>>
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
     * @return list<array<string, string|array<int,int>>>
     */
    public function getAvailableFormations(): array
    {
        return $this->availableFormations;
    }

    /**
     * @param list<array<string, string|array<int,int>>> $availableFormations
     */
    public function setAvailableFormations( array $availableFormations ): void
    {
        $this->availableFormations = $availableFormations;
    }
}
