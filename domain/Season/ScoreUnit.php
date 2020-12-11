<?php

declare(strict_types=1);

namespace SuperElf\Season;

use Sports\Season;
use SuperElf\ScoreUnit as ScoreUnitBase;

class ScoreUnit {
    protected int $id;
    protected Season $season;
    protected int $number;
    protected $points;

    public function __construct(Season $season, ScoreUnitBase $scoreUnit, int $points )
    {
        $this->setSeason( $season );
        $this->number = $scoreUnit->getNumber();
        $this->points = $points;
    }

    public function getSeason(): Season {
        return $this->season;
    }

    protected function setSeason(Season $season)
    {
//        if (!$season->getScoreUnits()->contains($this)) {
//            $season->getScoreUnits()->add($this) ;
//        }
        $this->season = $season;
    }

    public function getBase(): ScoreUnitBase {
        return new ScoreUnitBase( $this->number );
    }

    public function getNumber(): int {
        return $this->number;
    }

    public function getPoints(): int {
        return $this->points;
    }
}