<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig\Output;

use Sports\NameService;
use SuperElf\CompetitionConfig;

final class RangeCalculator
{
    public const PADDING = 1;
    public const BORDER = 1;

    protected NameService $nameService;

    public function __construct()
    {
        $this->nameService = new NameService();
    }

    public function getMaxHeight(CompetitionConfig $competitionConfig): int
    {
        $height = 1;    // Title
        $height++;      // period-names
        $height++;      // period-start-end-date
        $height++;      // period-start-end-time
        $height++;      // border
        return $height + $this->getMaxNrOfGameRounds($competitionConfig);
    }

    protected function getMaxNrOfGameRounds(CompetitionConfig $competitionConfig): int
    {
        $createAndJoin = count($competitionConfig->getCreateAndJoinPeriod()->getGameRounds());
        $assembleView = count($competitionConfig->getAssemblePeriod()->getViewPeriod()->getGameRounds());
        $transferView = count($competitionConfig->getTransferPeriod()->getViewPeriod()->getGameRounds());
        return max($createAndJoin, $assembleView, $transferView);
    }

    public function getMaxWidth(int $titleLength): int
    {
        $tableWidth = $this->getMaxTableWidth();
        return max($tableWidth, $titleLength);
    }

    public function getMaxTableWidth(): int
    {
        $maxWidth = $this->getCreateAndJoinWidth() + self::BORDER;
        $maxWidth += $this->getMaxAssemblePeriodWidth() + self::BORDER;
        $maxWidth += $this->getAssembleViewPeriodWidth() + self::BORDER;
        $maxWidth += $this->getMaxTransferPeriodWidth() + self::BORDER;
        $maxWidth += $this->getTransferViewPeriodWidth();

        return $maxWidth;
    }

    public function getCreateAndJoinWidth(): int
    {
        $nameWidth = $this->getCreateAndJoinNameWidth();
        $gnrsWidth = $this->getViewPeriodGameRoundWidth();
        return max($nameWidth, $gnrsWidth, $this->getPeriodDatesWidth());
    }

    public function getCreateAndJoinNameWidth(): int
    {
        return self::PADDING + mb_strlen(DrawHelper::CreateAndJoinTitle) + self::PADDING;
    }

    public function getViewPeriodGameRoundWidth(): int
    {
        $maxNrOfGameRoundDigits = 2 + self::PADDING;
        return $maxNrOfGameRoundDigits + $this->getPeriodDatesWidth();
    }

    public function getPeriodDatesWidth(): int
    {
        return self::PADDING + 1 + 5 + 1 + self::PADDING + 5 + 1 + self::PADDING; // DrawHelper::DateFormat
    }

    public function getMaxAssemblePeriodWidth(): int
    {
        return max($this->getAssemblePeriodNameWidth(), $this->getPeriodDatesWidth());
    }

    public function getAssemblePeriodNameWidth(): int
    {
        return self::PADDING + mb_strlen(DrawHelper::AssemblePeriodTitle) + self::PADDING;
    }

    public function getAssembleViewPeriodWidth(): int
    {
        $nameWidth = $this->getAssembleViewPeriodNameWidth();
        $gnrsWidth = $this->getViewPeriodGameRoundWidth();
        return max($nameWidth, $gnrsWidth, $this->getPeriodDatesWidth());
    }

    public function getAssembleViewPeriodNameWidth(): int
    {
        return self::PADDING + mb_strlen(DrawHelper::AssembleViewPeriodTitle) + self::PADDING;
    }

    public function getMaxTransferPeriodWidth(): int
    {
        return max($this->getTransferPeriodNameWidth(), $this->getPeriodDatesWidth());
    }

    public function getTransferPeriodNameWidth(): int
    {
        return self::PADDING + mb_strlen(DrawHelper::TransferPeriodTitle) + self::PADDING;
    }

    public function getTransferViewPeriodWidth(): int
    {
        $nameWidth = $this->getTransferViewPeriodNameWidth();
        $gnrsWidth = $this->getViewPeriodGameRoundWidth();
        return max($nameWidth, $gnrsWidth);
    }

    public function getTransferViewPeriodNameWidth(): int
    {
        return self::PADDING + mb_strlen(DrawHelper::TransferViewPeriodTitle) + self::PADDING;
    }
}
