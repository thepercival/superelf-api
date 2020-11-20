<?php

namespace SuperElf\Pool;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Formation;
use SuperElf\Pool;
use League\Period\Period as BasePeriod;

abstract class Period {
    protected int $id;
    protected DateTimeImmutable $startDateTime;
    protected DateTimeImmutable $endDateTime;

    public function __construct(BasePeriod $period)
    {

        $this->startDateTime = $period->getStartDate();
        $this->endDateTime = $period->getEndDate();
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