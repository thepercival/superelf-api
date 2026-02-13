<?php

declare(strict_types=1);

namespace SuperElf;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Defaults
{
    public const int MAXNROFTRANSFERS = 2;
    public const int SPOTTY_SHEET_THRESHOLD = 3;
    public const int POINTS_WIN = 3;
    public const int POINTS_DRAW = 1;
    public const int GOAL_GOALKEEPER = 10;
    public const int GOAL_DEFENDER = 5;
    public const int GOAL_MIDFIELDER = 4;
    public const int GOAL_FORWARD = 3;
    public const int ASSIST_GOALKEEPER = 3;
    public const int ASSIST_DEFENDER = 2;
    public const int ASSIST_MIDFIELDER = 1;
    public const int ASSIST_FORWARD = 1;
    public const int GOAL_PENALTY = 1;
    public const int GOAL_OWN = -4;
    public const int CLEAN_SHEET_GOALKEEPER = 4;
    public const int CLEAN_SHEET_DEFENDER = 2;
    public const int SPOTTY_SHEET_GOALKEEPER = -2;
    public const int SPOTTY_SHEET_DEFENDER = -1;
    public const int CARD_YELLOW = -1;
    public const int CARD_RED = -4;

    public function __construct(
    ) {
    }
}
