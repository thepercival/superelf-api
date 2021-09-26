<?php
declare(strict_types=1);

namespace SuperElf\Pool;

use SuperElf\GameRound;
use SuperElf\GameRound\Score as BaseGameRoundScore;
use SuperElf\Pool;

class GameRoundScore extends BaseGameRoundScore {
    public function __construct(protected Pool $pool, GameRound $gameRound )
    {
        parent::__construct($gameRound);
    }

    public function getPool(): Pool {
        return $this->pool;
    }
}