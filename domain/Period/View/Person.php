<?php

namespace SuperElf\Period\View;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Competition;
use Sports\Person as BasePerson;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\View\Person\GameRoundScore;

class Person {
    public const SHEET_SPOTTY_THRESHOLD = 4;

    public const RESULT = 1;
    public const GOALS_FIELD = 2;
    public const GOALS_PENALTY = 4;
    public const GOALS_OWN = 8;
    public const ASSISTS = 16;
    public const SHEET_CLEAN = 32;
    public const SHEET_SPOTTY = 64;
    public const CARDS_YELLOW = 128;
    public const CARD_RED = 256;
    public const LINEUP = 512;
    public const SUBSTITUTED = 1024;
    public const SUBSTITUTE = 2048;
    public const LINE = 4096;

    protected int $id;
    protected BasePerson $person;
    protected int $total = 0;
    /**
     * @var array | int[]
     */
    protected array $points = [];
    protected ViewPeriod $viewPeriod;
    /**
     * @var ArrayCollection | GameRoundScore[]
     */
    protected $gameRoundScores;

    public function __construct( ViewPeriod $viewPeriod, BasePerson $person )
    {
        $this->viewPeriod = $viewPeriod;
        $this->person = $person;
        $this->gameRoundScores = new ArrayCollection();
    }

    public function getViewPeriod(): ViewPeriod {
        return $this->viewPeriod;
    }

    public function getPerson(): BasePerson {
        return $this->person;
    }

    /**
     * @return ArrayCollection | GameRoundScore[]
     */
    public function getGameRoundScores() {
        return $this->gameRoundScores;
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