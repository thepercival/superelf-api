<?php

namespace SuperElf\GameRound;

use SuperElf\GameRound as BaseGameRound;

abstract class Score {
    protected int $id;
    protected BaseGameRound $gameRound;
    protected int $total = 0;
    /**
     * @var array | int[]
     */
    protected array $points = [];

    public function __construct(BaseGameRound $gameRound )
    {
        $this->gameRound = $gameRound;
    }

    public function getGameRound(): BaseGameRound {
        return $this->gameRound;
    }

    public function getGameRoundNumber(): int {
        return $this->gameRound->getNumber();
    }

    public function getTotal(): int {
        return $this->total;
    }

    public function setTotal(int $total) {
        $this->total = $total;
    }

    /**
     * @return array|int[]
     */
    public function getPoints(): array {
        return $this->points;
    }

    /**
     * @param array|int[] $points
     */
    public function setPoints(array $points ) {
        $this->points = $points;
    }
}