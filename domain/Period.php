<?php

declare(strict_types=1);

namespace SuperElf;

use DateTime;
use DateTimeImmutable;
use League\Period\Period as BasePeriod;
use Sports\Competition;
use SportsHelpers\Identifiable;
use Stringable;

abstract class Period extends Identifiable implements Stringable
{
    protected DateTimeImmutable $startDateTime;
    protected DateTimeImmutable $endDateTime;

    public function __construct(protected Competition $sourceCompetition, BasePeriod $period)
    {
        $this->startDateTime = $period->getStartDate();
        $this->endDateTime = $period->getEndDate();
    }

    public function getSourceCompetition(): Competition
    {
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

    public function contains(DateTimeImmutable $date = null): bool
    {
        return $this->getPeriod()->contains($date !== null ? $date : new DateTimeImmutable()) ;
    }

    public function __toString(): string
    {
        return $this->getStartDateTime()->format(DateTime::ISO8601) .
            ' => ' .
            $this->getEndDateTime()->format(DateTime::ISO8601);
    }
}
