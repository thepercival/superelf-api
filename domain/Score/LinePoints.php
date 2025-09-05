<?php

declare(strict_types=1);

namespace SuperElf\Score;

use Sports\Sport\FootballLine;
use SuperElf\FootballScore;

final class LinePoints extends Points
{
    public function __construct(private FootballLine $line, FootballScore $score, int $points)
    {
        parent::__construct($score, $points);
    }

    public function getLineNative(): int
    {
        return $this->line->value;
    }

    public function getId(): string
    {
        return $this->line->value . '-' . $this->score->value;
    }
}
