<?php
declare(strict_types=1);

namespace SuperElf;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Competition;
use Sports\Formation;

class ActiveConfig
{
    protected DateTimeImmutable $createAndJoinStart;
    protected DateTimeImmutable $createAndJoinEnd;
    /**
     * @var list<Formation>
     */
    protected array $availableFormations = [];

    /**
     * @param Period $createAndJoinPeriod
     * @param list<array<string, int|string|null>> $sourceCompetitions
     */
    public function __construct(Period $createAndJoinPeriod, protected array $sourceCompetitions)
    {
        $this->createAndJoinStart = $createAndJoinPeriod->getStartDate();
        $this->createAndJoinEnd = $createAndJoinPeriod->getEndDate();
    }

    public function getCreateAndJoinPeriod(): Period
    {
        return new Period($this->createAndJoinStart, $this->createAndJoinEnd);
    }

    /**
     * @return list<array<string, int|string|null>>
     */
    public function getSourceCompetitions(): array
    {
        return $this->sourceCompetitions;
    }

    /**
     * @return list<Formation>
     */
    public function getAvailableFormations(): array
    {
        return $this->availableFormations;
    }

    /**
     * @param list<Formation> $availableFormations
     */
    public function setAvailableFormations(array $availableFormations): void
    {
        $this->availableFormations = $availableFormations;
    }
}
