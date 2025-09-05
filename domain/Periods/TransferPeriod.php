<?php

declare(strict_types=1);

namespace SuperElf\Periods;

use League\Period\Period;
use SuperElf\Period as BasePeriod;
use SuperElf\Periods\ViewPeriod as ViewPeriod;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class TransferPeriod extends BasePeriod
{
    protected int $maxNrOfTransfers;
    protected ViewPeriod $viewPeriod;

    public function __construct(Period $period, ViewPeriod $viewPeriod, int $maxNrOfTransfers)
    {
        parent::__construct($period);
        $this->viewPeriod = $viewPeriod;
        $this->maxNrOfTransfers = $maxNrOfTransfers;
    }

    public function getViewPeriod(): ViewPeriod
    {
        return $this->viewPeriod;
    }

    public function getMaxNrOfTransfers(): int
    {
        return $this->maxNrOfTransfers;
    }

    public function setMaxNrOfTransfers(int $maxNrOfTransfers): void
    {
        $this->maxNrOfTransfers = $maxNrOfTransfers;
    }
}
