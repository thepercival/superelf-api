<?php

namespace SuperElf\Pool\User;

use SuperElf\Pool\User as PoolUser;
use SuperElf\GameRound\Score as BaseGameRoundScore;
use SuperElf\GameRound;

class GameRoundScore extends BaseGameRoundScore {
    protected PoolUser $poolUser;

    public function __construct(PoolUser $poolUser, GameRound $gameRound )
    {
        parent::__construct($gameRound);
        $this->setPoolUser( $poolUser );
    }

    public function getPoolUser(): PoolUser {
        return $this->getPoolUser();
    }

    protected function setPoolUser(PoolUser $poolUser)
    {
        if (!$poolUser->getScores()->contains($this)) {
            $poolUser->getScores()->add($this) ;
        }
        $this->poolUser = $poolUser;
    }
}