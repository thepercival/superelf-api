<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig\Output;

use Sports\NameService;
use Sports\Output\Coordinate;
use Sports\Output\Grid\Align;
use Sports\Output\Grid\Drawer;
use SportsHelpers\Output\Color;
use SuperElf\CompetitionConfig;
use SuperElf\GameRound;
use SuperElf\Period\View as ViewPeriod;

final class DrawHelper
{
    use Color;

    public const CreateAndJoinTitle = 'create & join';
    public const AssemblePeriodTitle = 'assemble';
    public const AssembleViewPeriodTitle = 'assemble-viewperiod';
    public const TransferPeriodTitle = 'transfer';
    public const TransferViewPeriodTitle = 'transfer-viewperiod';
    public const MaxLeagueNameChars = 10;
    public const DateFormat = 'd/m';
    protected NameService $nameService;
    protected RangeCalculator $rangeCalculator;

    public function __construct(protected Drawer $drawer)
    {
        $this->nameService = new NameService();
        $this->rangeCalculator = new RangeCalculator();
    }

    public function draw(CompetitionConfig $competitionConfig, Coordinate $origin, string $title): void
    {
        $coordinate = $this->drawTitle($title, $origin);
        $coordinate = $this->drawPeriodNames($competitionConfig, $coordinate);
        $coordinate = $this->drawPeriodDates($competitionConfig, $coordinate);
        $this->drawGameRoundNumbers($competitionConfig, $coordinate);
    }

    public function drawTitle(string $title, Coordinate $origin): Coordinate
    {
        $this->drawer->drawCellToRight($origin, $title, $this->drawer->getMaxWidth(), Align::Center);
        return $origin->incrementY();
    }

    public function drawPeriodNames(CompetitionConfig $competitionConfig, Coordinate $origin): Coordinate
    {
        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            DrawHelper::CreateAndJoinTitle,
            $this->rangeCalculator->getMaxCreateAndJoinWidth($competitionConfig),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            DrawHelper::AssemblePeriodTitle,
            $this->rangeCalculator->getMaxAssemblePeriodWidth(),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            DrawHelper::AssembleViewPeriodTitle,
            $this->rangeCalculator->getMaxAssembleViewPeriodWidth($competitionConfig),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            DrawHelper::TransferPeriodTitle,
            $this->rangeCalculator->getMaxTransferPeriodWidth(),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $this->drawer->drawCellToRight(
            $coordinate,
            DrawHelper::TransferViewPeriodTitle,
            $this->rangeCalculator->getMaxTransferViewPeriodWidth($competitionConfig),
            Align::Center
        );
        return $origin->incrementY();
    }

    public function drawBorder(Coordinate $coordinate): Coordinate
    {
        return $this->drawer->drawToRight($coordinate, '|')->incrementX();
    }

    public function drawPeriodDates(CompetitionConfig $competitionConfig, Coordinate $origin): Coordinate
    {
        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            $competitionConfig->getCreateAndJoinPeriod()->getPeriod()->toIso80000(self::DateFormat),
            $this->rangeCalculator->getMaxCreateAndJoinWidth($competitionConfig),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $competitionConfig->getAssemblePeriod()->getPeriod()->toIso80000(self::DateFormat),
            $this->rangeCalculator->getMaxAssemblePeriodWidth(),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $competitionConfig->getAssemblePeriod()->getViewPeriod()->getPeriod()->toIso80000(self::DateFormat),
            $this->rangeCalculator->getMaxAssembleViewPeriodWidth($competitionConfig),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $competitionConfig->getTransferPeriod()->getPeriod()->toIso80000(self::DateFormat),
            $this->rangeCalculator->getMaxTransferPeriodWidth(),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $this->drawer->drawCellToRight(
            $coordinate,
            $competitionConfig->getTransferPeriod()->getViewPeriod()->getPeriod()->toIso80000(self::DateFormat),
            $this->rangeCalculator->getMaxTransferViewPeriodWidth($competitionConfig),
            Align::Center
        );
        return $origin->incrementY();
    }

    public function drawGameRoundNumbers(CompetitionConfig $competitionConfig, Coordinate $origin): Coordinate
    {
        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            $this->getGameRoundNumbers($competitionConfig->getCreateAndJoinPeriod()),
            $this->rangeCalculator->getMaxCreateAndJoinWidth($competitionConfig),
            Align::Left
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $coordinate->addX($this->rangeCalculator->getMaxAssemblePeriodWidth());
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $this->getGameRoundNumbers($competitionConfig->getAssemblePeriod()->getViewPeriod()),
            $this->rangeCalculator->getMaxAssembleViewPeriodWidth($competitionConfig),
            Align::Left
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $coordinate->addX($this->rangeCalculator->getMaxTransferPeriodWidth());
        $coordinate = $this->drawBorder($coordinate);
        $this->drawer->drawCellToRight(
            $coordinate,
            $this->getGameRoundNumbers($competitionConfig->getTransferPeriod()->getViewPeriod()),
            $this->rangeCalculator->getMaxTransferViewPeriodWidth($competitionConfig),
            Align::Left
        );
        return $origin->incrementY();
    }

    private function getGameRoundNumbers(ViewPeriod $viewPeriod): string
    {
        return join(
            ',',
            $viewPeriod->getGameRounds()->map(function (GameRound $gameRound): string {
                return (string)$gameRound->getNumber();
            })->toArray()
        );
    }
}
