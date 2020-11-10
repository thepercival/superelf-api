<?php

namespace SuperElf;

use Sports\Team;
use Sports\Sport\Formation\Line as FormationLine;

class ScoreUnit
{
    public const POINTS_WIN = 1;
    public const POINTS_DRAW = 2;
    public const GOAL_GOALKEEPER = 4;
    public const GOAL_DEFENDER = 8;
    public const GOAL_MIDFIELDER = 16;
    public const GOAL_FORWARD = 32;
    public const ASSIST_GOALKEEPER = 64;
    public const ASSIST_DEFENDER = 128;
    public const ASSIST_MIDFIELDER = 256;
    public const ASSIST_FORWARD = 512;
    public const GOAL_PENALTY = 1024;
    public const GOAL_OWN = 2048;
    public const SHEET_CLEAN_GOALKEEPER = 4096;
    public const SHEET_CLEAN_DEFENDER = 8192;
    public const SHEET_SPOTTY_GOALKEEPER = 16384;
    public const SHEET_SPOTTY_DEFENDER = 32768;
    public const CARD_YELLOW = 65536;
    public const CARD_RED = 131072;

    protected int $id;
    protected int $number;

    public function __construct(int $number)
    {
        $this->number = $number;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getLines(): int
    {
        switch ($this->id) {
            case self::GOAL_GOALKEEPER:
            case self::ASSIST_GOALKEEPER:
            case self::SHEET_CLEAN_GOALKEEPER:
            case self::SHEET_SPOTTY_GOALKEEPER:
                return FormationLine::GOALKEEPER;
            case self::GOAL_DEFENDER:
            case self::ASSIST_DEFENDER:
            case self::SHEET_CLEAN_DEFENDER:
            case self::SHEET_SPOTTY_DEFENDER:
                return FormationLine::DEFENSE;
            case self::GOAL_MIDFIELDER:
            case self::ASSIST_MIDFIELDER:
                return FormationLine::MIDFIELD;
            case self::GOAL_FORWARD:
            case self::ASSIST_FORWARD:
                return FormationLine::FORWARD;
        }
        return FormationLine::FOOTBALL_ALL;
    }
}
