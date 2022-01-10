<?php

declare(strict_types=1);

namespace SuperElf\Points;

use SuperElf\Defaults;
use SuperElf\Points;

class Creator
{
    public function __construct()
    {
    }

    public function createDefault(): Points
    {
        return new Points(
            Defaults::POINTS_WIN,
            Defaults::POINTS_DRAW,
            Defaults::GOAL_GOALKEEPER,
            Defaults::GOAL_DEFENDER,
            Defaults::GOAL_MIDFIELDER,
            Defaults::GOAL_FORWARD,
            Defaults::ASSIST_GOALKEEPER,
            Defaults::ASSIST_DEFENDER,
            Defaults::ASSIST_MIDFIELDER,
            Defaults::ASSIST_FORWARD,
            Defaults::GOAL_PENALTY,
            Defaults::GOAL_OWN,
            Defaults::CLEAN_SHEET_GOALKEEPER,
            Defaults::CLEAN_SHEET_DEFENDER,
            Defaults::SPOTTY_SHEET_GOALKEEPER,
            Defaults::SPOTTY_SHEET_DEFENDER,
            Defaults::CARD_YELLOW,
            Defaults::CARD_RED
        );
    }
}
