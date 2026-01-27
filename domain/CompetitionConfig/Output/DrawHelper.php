<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig\Output;

use Sports\Game\Against as AgainstGame;
use Sports\NameService;
use Sports\Output\Coordinate;
use Sports\Output\Grid\Align;
use Sports\Output\Grid\Drawer;
use SportsHelpers\Output\Color;
use SuperElf\CompetitionConfig;
use SuperElf\GameRound;
use SuperElf\GameRound\PeriodCalculator as GameRoundPeriodCalculator;

final class DrawHelper
{
    public const CreateAndJoinTitle = 'create & join';
    public const AssemblePeriodTitle = 'assemble';
    public const AssembleViewPeriodTitle = 'assemble-viewperiod';
    public const TransferPeriodTitle = 'transfer';
    public const TransferViewPeriodTitle = 'transfer-viewperiod';
    public const MaxLeagueNameChars = 10;
    public const DateFormat = 'd/m';
    public const TimeFormat = 'H:s';
    protected NameService $nameService;
    protected RangeCalculator $rangeCalculator;
    protected GameRoundPeriodCalculator $gameRoundPeriodCalculator;

    public function __construct(protected Drawer $drawer)
    {
        $this->nameService = new NameService();
        $this->rangeCalculator = new RangeCalculator();
        $this->gameRoundPeriodCalculator = new GameRoundPeriodCalculator();
    }

    /**
     * @param CompetitionConfig $competitionConfig
     * @param list<AgainstGame> $againstGames
     * @param Coordinate $origin
     * @param string $title
     */
    public function draw(
        CompetitionConfig $competitionConfig,
        array $againstGames,
        Coordinate $origin,
        string $title
    ): void {
        $coordinate = $this->drawTitle($title, $origin);
        $coordinate = $this->drawPeriodNames($competitionConfig, $coordinate);
        $coordinate = $this->drawPeriodDateTimes($competitionConfig, $coordinate, self::DateFormat);
        $coordinate = $this->drawPeriodDateTimes($competitionConfig, $coordinate, self::TimeFormat);
        $coordinate = $this->drawBorderRow($coordinate);
        $this->drawGameRoundNumbers($competitionConfig, $againstGames, $coordinate);
    }

    public function drawTitle(string $title, Coordinate $origin): Coordinate
    {
        $this->drawer->drawCellToRight($origin, $title, $this->drawer->getGridWidth(), Align::Center);
        return $origin->incrementY();
    }

    public function drawPeriodNames(CompetitionConfig $competitionConfig, Coordinate $origin): Coordinate
    {
        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            DrawHelper::CreateAndJoinTitle,
            $this->rangeCalculator->getCreateAndJoinWidth(),
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
            $this->rangeCalculator->getAssembleViewPeriodWidth(),
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
            $this->rangeCalculator->getTransferViewPeriodWidth(),
            Align::Center
        );
        return $origin->incrementY();
    }

    public function drawBorder(Coordinate $coordinate): Coordinate
    {
        return $this->drawer->drawToRight($coordinate, '|')->incrementX();
    }

    public function drawPeriodDateTimes(CompetitionConfig $competitionConfig, Coordinate $origin, string $format): Coordinate
    {
        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            $competitionConfig->getCreateAndJoinPeriod()->getPeriod()->toIso80000($format),
            $this->rangeCalculator->getCreateAndJoinWidth(),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $competitionConfig->getAssemblePeriod()->getPeriod()->toIso80000($format),
            $this->rangeCalculator->getMaxAssemblePeriodWidth(),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $competitionConfig->getAssemblePeriod()->getViewPeriod()->getPeriod()->toIso80000($format),
            $this->rangeCalculator->getAssembleViewPeriodWidth(),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $competitionConfig->getTransferPeriod()->getPeriod()->toIso80000($format),
            $this->rangeCalculator->getMaxTransferPeriodWidth(),
            Align::Center
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $this->drawer->drawCellToRight(
            $coordinate,
            $competitionConfig->getTransferPeriod()->getViewPeriod()->getPeriod()->toIso80000($format),
            $this->rangeCalculator->getTransferViewPeriodWidth(),
            Align::Center
        );
        return $origin->incrementY();
    }

    public function drawBorderRow(Coordinate $origin): Coordinate
    {
        $border = '';
        for ($i = 0; $i < $this->drawer->getGridWidth(); $i++) {
            $border .= '-';
        }
        $this->drawer->drawCellToRight($origin, $border, $this->drawer->getGridWidth(), Align::Left);
        return $origin->incrementY();
    }

    /**
     * @param CompetitionConfig $competitionConfig
     * @param list<AgainstGame> $againstGames
     * @param Coordinate $origin
     * @return Coordinate
     */
    public function drawGameRoundNumbers(
        CompetitionConfig $competitionConfig,
        array $againstGames,
        Coordinate $origin
    ): Coordinate {
        $createAndJoinGameRounds = $competitionConfig->getCreateAndJoinPeriod()->getGameRounds()->toArray();
        $assembleViewGameRounds = $competitionConfig->getAssemblePeriod()->getViewPeriod()->getGameRounds()->toArray();
        $transferViewGameRounds = $competitionConfig->getTransferPeriod()->getViewPeriod()->getGameRounds()->toArray();

        $coordinate = $origin;
        $createAndJoinGameRound = array_shift($createAndJoinGameRounds);
        $assembleViewGameRound = array_shift($assembleViewGameRounds);
        $transferViewGameRound = array_shift($transferViewGameRounds);
        while ($createAndJoinGameRound !== null || $assembleViewGameRound !== null || $transferViewGameRound !== null) {
            $this->drawGameRoundNumberRow(
                $againstGames,
                $coordinate,
                $createAndJoinGameRound,
                $assembleViewGameRound,
                $transferViewGameRound
            );

            $coordinate = $coordinate->incrementY();
            $createAndJoinGameRound = array_shift($createAndJoinGameRounds);
            $assembleViewGameRound = array_shift($assembleViewGameRounds);
            $transferViewGameRound = array_shift($transferViewGameRounds);
        }
        return $origin->incrementY();
    }

    /**
     * @param list<AgainstGame> $againstGames
     * @param Coordinate $origin
     * @param GameRound|null $createAndJoinGameRound
     * @param GameRound|null $assembleGameRound
     * @param GameRound|null $transferGameRound
     * @return Coordinate
     */
    public function drawGameRoundNumberRow(
        array $againstGames,
        Coordinate $origin,
        GameRound|null $createAndJoinGameRound,
        GameRound|null $assembleGameRound,
        GameRound|null $transferGameRound,
    ): Coordinate {
        $coordinate = $this->drawGameRoundNumber(
            $origin,
            $againstGames,
            $createAndJoinGameRound,
            $this->rangeCalculator->getCreateAndJoinWidth()
        );

        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $coordinate->addX($this->rangeCalculator->getMaxAssemblePeriodWidth());
        $coordinate = $this->drawBorder($coordinate);

        $coordinate = $this->drawGameRoundNumber(
            $coordinate,
            $againstGames,
            $assembleGameRound,
            $this->rangeCalculator->getAssembleViewPeriodWidth()
        );

        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $coordinate->addX($this->rangeCalculator->getMaxTransferPeriodWidth());
        $coordinate = $this->drawBorder($coordinate);

        $this->drawGameRoundNumber(
            $coordinate,
            $againstGames,
            $transferGameRound,
            $this->rangeCalculator->getTransferViewPeriodWidth()
        );

        return $origin->incrementY();
    }

    /**
     * @param Coordinate $origin
     * @param list<AgainstGame> $againstGames
     * @param GameRound|null $gameRound
     * @param int $viewPeriodWidth
     * @return Coordinate
     */
    public function drawGameRoundNumber(
        Coordinate $origin,
        array $againstGames,
        GameRound|null $gameRound,
        int $viewPeriodWidth
    ): Coordinate {
        $text = ' ';
        $color = Color::White;
        if ($gameRound !== null) {
            if ($gameRound->getNumber() < 10) {
                $text .= ' ';
            }
            $text .= $gameRound->getNumber();
            $text .= ' ';
            $period = $this->gameRoundPeriodCalculator->getGameRoundPeriod($gameRound, $againstGames);
            if ($period !== null) {
                $text .= $period->toIso80000(self::DateFormat);
                if (!$gameRound->getViewPeriod()->getPeriod()->contains($period)) {
                    $color = Color::Red;
                }
            }
        }
        return $this->drawer->drawCellToRight(
            $origin,
            $text,
            $viewPeriodWidth,
            Align::Left,
            $color
        )->incrementX();
    }

//    private function getGameRoundNumbers(ViewPeriod $viewPeriod): string
//    {
//        return join(
//            ',',
//            $viewPeriod->getGameRounds()->map(function (GameRound $gameRound): string {
//                return (string)$gameRound->getNumber();
//            })->toArray()
//        );
//    }
}
