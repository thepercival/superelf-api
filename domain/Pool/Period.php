<?php

namespace SuperElf\Pool;

use DateTimeImmutable;
use SuperElf\Pool;
use League\Period\Period as BasePeriod;

class Period {

    public const CREATE_AND_JOIN = 1;
    public const CHOOSE_PLAYERS = 2;
    public const TRANSFER = 4;

    protected int $id;
    protected Pool $pool;
    protected int $type;
    protected DateTimeImmutable $startDateTime;
    protected DateTimeImmutable $endDateTime;

    public function __construct(Pool $pool, BasePeriod $period, int $type)
    {
        $this->setPool( $pool );
        $this->startDateTime = $period->getStartDate();
        $this->endDateTime = $period->getEndDate();
        $this->type = $type;
    }

    public function getPool(): Pool {
        return $this->getPool();
    }

    public function setPool(Pool $pool)
    {
        if (!$pool->getPeriods()->contains($this)) {
            $pool->getPeriods()->add($this) ;
        }
        $this->pool = $pool;
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
}