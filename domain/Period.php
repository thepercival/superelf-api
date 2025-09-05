<?php

declare(strict_types=1);

namespace SuperElf;

use DateTime;
use DateTimeImmutable;
use League\Period\Period as LeaguePeriod;
use SportsHelpers\Identifiable;
use Stringable;

class Period extends Identifiable
{
    protected DateTimeImmutable $startDateTime;
    protected DateTimeImmutable $endDateTime;

    public function __construct(LeaguePeriod $period)
    {
        $this->startDateTime = $period->getStartDate();
        $this->endDateTime = $period->getEndDate();
    }

    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(DateTimeImmutable $dateTime): void
    {
        $this->startDateTime = $dateTime;
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        return $this->endDateTime;
    }

    public function setEndDateTime(DateTimeImmutable $dateTime): void
    {
        $this->endDateTime = $dateTime;
    }

    public function getPeriod(): LeaguePeriod
    {
        return new LeaguePeriod($this->getStartDateTime(), $this->getEndDateTime());
    }

    public function contains(DateTimeImmutable $date = null): bool
    {
        return $this->getPeriod()->contains($date !== null ? $date : new DateTimeImmutable());
    }
}
