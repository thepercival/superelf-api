<?php
declare(strict_types=1);

namespace SuperElf\Season;

use Sports\Season;
use SportsHelpers\Identifiable;
use SuperElf\ScoreUnit as ScoreUnitBase;

class ScoreUnit extends Identifiable {
    protected Season $season;
    protected int $number;

    public function __construct(Season $season, ScoreUnitBase $scoreUnit, protected int $points )
    {
        $this->setSeason( $season );
        $this->number = $scoreUnit->getNumber();
    }

    public function getSeason(): Season {
        return $this->season;
    }

    protected function setSeason(Season $season): void
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