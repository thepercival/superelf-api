<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig\Output;

use Sports\NameService;
use SuperElf\CompetitionConfig;
use SuperElf\GameRound;
use SuperElf\Period\View as ViewPeriod;

final class RangeCalculator
{
    // protected const MARGIN = 1;
    public const PADDING = 1;
    public const BORDER = 1;
//    protected const CREATEANDJOINWIDTH = 3;
//    protected const HORPLACEWIDTH = 3;
    public const GameRoundNumberTitle = 'grnr';

    protected NameService $nameService;

    public function __construct()
    {
        $this->nameService = new NameService();
    }

    public function getHeight(): int
    {
        // 1 Title
        // 2 period-names
        // 3 period-start-end
        // 4 gamenumbers
        return 4;
    }

    public function getMaxWidth(CompetitionConfig $competitionConfig, int $titleLength): int
    {
        $tableWidth = $this->getMaxTableWidth($competitionConfig);
        return max($tableWidth, $titleLength);
    }

    public function getMaxTableWidth(CompetitionConfig $competitionConfig): int
    {
        $maxWidth = $this->getMaxCreateAndJoinWidth($competitionConfig) + self::BORDER;
        $maxWidth += $this->getMaxAssemblePeriodWidth() + self::BORDER;
        $maxWidth += $this->getMaxAssembleViewPeriodWidth($competitionConfig) + self::BORDER;
        $maxWidth += $this->getMaxTransferPeriodWidth() + self::BORDER;
        $maxWidth += $this->getMaxTransferViewPeriodWidth($competitionConfig);

        return $maxWidth;
    }

    public function getMaxCreateAndJoinWidth(CompetitionConfig $competitionConfig): int
    {
        $nameWidth = $this->getCreateAndJoinNameWidth();
        $gnrsWidth = $this->getViewPeriodGameRoundNrsWidth($competitionConfig->getCreateAndJoinPeriod());
        return max($nameWidth, $gnrsWidth, $this->getPeriodDatesWidth());
    }

    public function getCreateAndJoinNameWidth(): int
    {
        return self::PADDING + mb_strlen(DrawHelper::CreateAndJoinTitle) + self::PADDING;
    }

    public function getViewPeriodGameRoundNrsWidth(ViewPeriod $viewPeriod): int
    {
        return self::PADDING + mb_strlen(
                join(
                    ',',
                    $viewPeriod->getGameRounds()->map(function (GameRound $gameRound): string {
                        return (string)$gameRound->getNumber();
                    })->toArray()
                )
            ) + self::PADDING;
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

    public function getMaxAssembleViewPeriodWidth(CompetitionConfig $competitionConfig): int
    {
        $nameWidth = $this->getAssembleViewPeriodNameWidth();
        $gnrsWidth = $this->getViewPeriodGameRoundNrsWidth($competitionConfig->getAssemblePeriod()->getViewPeriod());
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

    public function getMaxTransferViewPeriodWidth(CompetitionConfig $competitionConfig): int
    {
        $nameWidth = $this->getTransferViewPeriodNameWidth();
        $gnrsWidth = $this->getViewPeriodGameRoundNrsWidth($competitionConfig->getTransferPeriod()->getViewPeriod());
        return max($nameWidth, $gnrsWidth);
    }

    public function getTransferViewPeriodNameWidth(): int
    {
        return self::PADDING + mb_strlen(DrawHelper::TransferViewPeriodTitle) + self::PADDING;
    }
}
