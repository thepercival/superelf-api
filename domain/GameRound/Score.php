<?php

namespace SuperElf\GameRound;

use SuperElf\GameRound as BaseGameRound;

abstract class Score {
    protected int $id;
    protected BaseGameRound $gameRound;
    protected int $points = 0;
    protected array $detailedPoints = [];

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

    public function getPoints(): int {
        return $this->points;
    }

    public function setPoints(int $points ) {
        $this->points = $points;
    }

    public function getDetailedPoints(): array {
        return $this->detailedPoints;
    }

    public function setDetailedPoints(array $detailedPoints ) {
        $this->detailedPoints = $detailedPoints;
    }
}