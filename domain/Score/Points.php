<?php

declare(strict_types=1);

namespace SuperElf\Score;

use SuperElf\FootballScore;

class Points
{
    public function __construct(protected FootballScore $score, protected int $points)
    {
    }

    public function getScoreNative(): string
    {
        return $this->score->value;
    }
}

// onderste twee vanuit competitionConfig mee laten komen!

//
//export interface LineScorePoints extends ScorePoints {
//line: FootballLine
//}