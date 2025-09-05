<?php

namespace SuperElf\Totals;

use Sports\Sport\FootballLine;
use SuperElf\Totals as TotalsBase;

/**
 * @psalm-api
 */
final class FormationLine
{
    public function __construct(protected FootballLine $line, protected TotalsBase $totals) {
    }

    public function getLine(): FootballLine {
        return $this->line;
    }

    public function getLineNative(): int
    {
        return $this->line->value;
    }

    public function getTotals(): TotalsBase {
        return $this->totals;
    }
}