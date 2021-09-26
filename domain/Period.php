<?php
declare(strict_types=1);

namespace SuperElf;

use DateTimeImmutable;
use League\Period\Period as BasePeriod;
use Sports\Competition;
use SportsHelpers\Identifiable;

abstract class Period extends Identifiable {
    protected DateTimeImmutable $startDateTime;
    protected DateTimeImmutable $endDateTime;

    public function __construct(protected Competition $sourceCompetition, BasePeriod $period)
    {
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