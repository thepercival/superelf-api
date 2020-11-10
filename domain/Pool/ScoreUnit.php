<?php

namespace SuperElf\Pool;

use SuperElf\Pool;
use SuperElf\ScoreUnit as ScoreUnitBase;

class ScoreUnit {
    protected int $id;
    protected Pool $pool;
    protected int $number;
    protected $points;

    public function __construct(Pool $pool, ScoreUnitBase $scoreUnit, int $points )
    {
        $this->setPool( $pool );
        $this->number = $scoreUnit->getNumber();
        $this->points = $points;
    }

    public function getPool(): Pool {
        return $this->getPool();
    }

    public function setPool(Pool $pool)
    {
        if (!$pool->getScoreUnits()->contains($this)) {
            $pool->getScoreUnits()->add($this) ;
        }
        $this->pool = $pool;
    }

    public function getBase(): ScoreUnitBase {
        return new ScoreUnitBase( $this->number );
    }

    public function getPoints(): int {
        return $this->points;
    }
}