<?php

declare(strict_types=1);

namespace SuperElf\Formation\Output;

use Sports\NameService;
use SuperElf\CompetitionConfig;
use SuperElf\Periods\ViewPeriod;

final class RangeCalculator
{
    public const PADDING = 1;
    public const BORDER = 1;

    protected NameService $nameService;

    public function __construct()
    {
        $this->nameService = new NameService();
    }

    public function getHeight(): int
    {
        $height = 1;    // Title
        $height += 1;  // gameRounds
        $height += 15;  // places
        $height += 1;      // totals
        return $height;
    }

    public function getWidth(ViewPeriod $viewPeriod): int
    {
        $maxWidth = $this->getLineWidth() + self::BORDER;
        $maxWidth += $this->getPlaceNrWidth()+ self::BORDER;
        $maxWidth += $this->getPersonNameWidth() + self::BORDER;
        $maxWidth += $this->getTeamAbbrWidth() + self::BORDER;
        $maxWidth += $this->getGameRoundsWidth($viewPeriod);
        $maxWidth += $this->getTotalsWidth();
        return $maxWidth;
    }

    public function getLineWidth(): int
    {
        return 1;
    }

    public function getPlaceNrWidth(): int
    {
        return 1;
    }

    public function getPersonNameWidth(): int
    {
        return 12;
    }

    public function getTeamAbbrWidth(): int
    {
        return 3;
    }

    public function getGameRoundWidth(): int
    {
        return 2;
    }

    public function getGameRoundsWidth(ViewPeriod $viewPeriod): int
    {
        return (($this->getGameRoundWidth() + self::BORDER) * count($viewPeriod->getGameRounds()));
    }

    public function getTotalsWidth(): int
    {
        return 3;
    }
}
