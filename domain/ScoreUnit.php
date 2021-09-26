<?php
declare(strict_types=1);

namespace SuperElf;

use Sports\Sport\Custom as SportCustom;
use SportsHelpers\Identifiable;

class ScoreUnit extends Identifiable
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

    public function __construct(protected int $number)
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
                return SportCustom::Football_Line_GoalKepeer;
            case self::GOAL_DEFENDER:
            case self::ASSIST_DEFENDER:
            case self::SHEET_CLEAN_DEFENDER:
            case self::SHEET_SPOTTY_DEFENDER:
                return SportCustom::Football_Line_Defense;
            case self::GOAL_MIDFIELDER:
            case self::ASSIST_MIDFIELDER:
                return SportCustom::Football_Line_Midfield;
            case self::GOAL_FORWARD:
            case self::ASSIST_FORWARD:
                return SportCustom::Football_Line_Forward;
        }
        return SportCustom::Football_Line_All;
    }
}
