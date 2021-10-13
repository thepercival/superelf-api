<?php
declare(strict_types=1);

namespace SuperElf;

use Sports\Season;
use SportsHelpers\Identifiable;

class Defaults
{
    public const MAXNROFTRANSFERS = 2;
    public const SPOTTY_SHEET_THRESHOLD = 3;
    public const POINTS_WIN = 3;
    public const POINTS_DRAW = 1;
    public const GOAL_GOALKEEPER = 10;
    public const GOAL_DEFENDER = 5;
    public const GOAL_MIDFIELDER = 4;
    public const GOAL_FORWARD = 3;
    public const ASSIST_GOALKEEPER = 3;
    public const ASSIST_DEFENDER = 2;
    public const ASSIST_MIDFIELDER = 1;
    public const ASSIST_FORWARD = 1;
    public const GOAL_PENALTY = 1;
    public const GOAL_OWN = -4;
    public const CLEAN_SHEET_GOALKEEPER = 4;
    public const CLEAN_SHEET_DEFENDER = 2;
    public const SPOTTY_SHEET_GOALKEEPER = -2;
    public const SPOTTY_SHEET_DEFENDER = -1;
    public const CARD_YELLOW = -1;
    public const CARD_RED = -4;

    public function __construct(
    ) {
    }
}
