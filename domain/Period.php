<?php

namespace SuperElf;

use DateTimeImmutable;
use League\Period\Period as BasePeriod;
use Sports\Competition;

abstract class Period {
    protected int $id;
    protected DateTimeImmutable $startDateTime;
    protected DateTimeImmutable $endDateTime;
    protected Competition $sourceCompetition;

    public function __construct(Competition $competition, BasePeriod $period)
    {
        $this->sourceCompetition = $competition;
        $this->startDateTime = $period->getStartDate();
        $this->endDateTime = $period->getEndDate();
    }

    public function getSourceCompetition(): Competition {
        return $this->sourceCompetition;
    }

    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        return $this->endDateTime;
    }

    public function getPeriod(): BasePeriod
    {
        return new BasePeriod($this->getStartDateTime(), $this->getEndDateTime());
    }

    public function contains(DateTimeImmutable $date = null): bool {
        return $this->getPeriod()->contains( $date !== null ? $date : new DateTimeImmutable() ) ;
    }
}