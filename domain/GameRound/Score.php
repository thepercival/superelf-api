<?php

namespace SuperElf\GameRound;

use SportsHelpers\Identifiable;
use SuperElf\GameRound as BaseGameRound;

abstract class Score extends Identifiable {
    protected BaseGameRound $gameRound;
    protected int $total = 0;
    /**
     * @var array<int, int>
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

    public function setTotal(int $total): void {
        $this->total = $total;
    }

    /**
     * @return array<int, int>
     */
    public function getPoints(): array {
        return $this->points;
    }

    /**
     * @param array<int, int> $points
     */
    public function setPoints(array $points ): void {
        $this->points = $points;
    }
}