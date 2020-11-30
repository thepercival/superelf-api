<?php

namespace SuperElf;

use Sports\Person;
use SuperElf\Period\View as ViewPeriod;

class PersonStats {
    public const SHEET_SPOTTY_THRESHOLD = 4;

    public const POINTS_WIN = 1;
    public const POINTS_DRAW = 2;
    public const GOALS_FIELD = 4;
    public const GOALS_PENALTY = 8;
    public const GOALS_OWN = 16;
    public const ASSISTS = 32;
    public const SHEET_CLEAN = 64;
    public const SHEET_SPOTTY = 128;
    public const CARDS_YELLOW = 256;
    public const CARD_RED = 512;
    public const LINEUP = 1024;
    public const SUBSTITUTED = 2048;
    public const SUBSTITUTE = 4096;

    protected int $id;
    protected Person $person;
    protected array $stats;
    protected ViewPeriod $viewPeriod;
    protected int $gameRound = 0;

    public function __construct(Person $person, array $stats, ViewPeriod $viewPeriod )
    {
        $this->person = $person;
        $this->stats = $stats;
        $this->viewPeriod = $viewPeriod;
    }

    public function getPerson(): Person {
        return $this->person;
    }

    public function getStats(): array {
        return $this->stats;
    }

    public function getViewPeriod(): ViewPeriod {
        return $this->viewPeriod;
    }

    public function getGameRound(): int {
        return $this->gameRound;
    }

    public function setGameRound( int $gameRound ) {
        $this->gameRound = $gameRound;
    }
}