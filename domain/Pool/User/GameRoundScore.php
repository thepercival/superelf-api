<?php

declare(strict_types=1);

namespace SuperElf\Pool\User;

use SuperElf\GameRound;
use SuperElf\GameRound\Score as BaseGameRoundScore;
use SuperElf\Pool\User as PoolUser;

class GameRoundScore extends BaseGameRoundScore
{
    public function __construct(protected PoolUser $poolUser, GameRound $gameRound)
    {
        parent::__construct($gameRound);
    }

    public function getPoolUser(): PoolUser
    {
        return $this->getPoolUser();
    }
}
