<?php

namespace SuperElf\Pool;

use SuperElf\GameRound;
use SuperElf\GameRound\Score as BaseGameRoundScore;
use SuperElf\Pool;

class GameRoundScore extends BaseGameRoundScore {
    protected Pool $pool;

    public function __construct(Pool $pool, GameRound $gameRound )
    {
        parent::__construct($gameRound);
        $this->setPool( $pool );
    }

    public function getPool(): Pool {
        return $this->getPool();
    }

    protected function setPool(Pool $pool)
    {
        if (!$pool->getScores()->contains($this)) {
            $pool->getScores()->add($this) ;
        }
        $this->pool = $pool;
    }
}