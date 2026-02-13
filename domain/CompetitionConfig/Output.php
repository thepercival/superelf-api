<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig;

use Psr\Log\LoggerInterface;
use Sports\Game\Against as AgainstGame;
use Sports\Output\Coordinate;
use Sports\Output\Grid;
use Sports\Output\Grid\Drawer as GridDrawer;
use SportsHelpers\Output\OutputAbstract;
use SuperElf\CompetitionConfig as CompetitionConfig;
use SuperElf\CompetitionConfig\Output\DrawHelper;
use SuperElf\CompetitionConfig\Output\RangeCalculator;

final class Output extends OutputAbstract
{
    protected RangeCalculator $rangeCalculator;
    /**
     * @var array<int, string>
     */
    protected array $outputLines = [];

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->rangeCalculator = new RangeCalculator();
    }

    /**
     * @param CompetitionConfig $competitionConfig
     * @param list<AgainstGame> $againstGames
     */
    public function output(CompetitionConfig $competitionConfig, array $againstGames): void
    {
        $title = $this->getTitle($competitionConfig);
        $grid = $this->getGrid($competitionConfig, mb_strlen($title));
        $drawer = new GridDrawer($grid);
        $coordinate = new Coordinate(0, 0);
        $drawHelper = new DrawHelper($drawer);
        $drawHelper->draw($competitionConfig, $againstGames, $coordinate, $title);

//        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
//        $retVal = 'batch ' . ($batchNr < 10 ? ' ' : '') . $batchNr;
//        return $this->outputColor($batchColor, $retVal);
        $grid->output();
    }

    public function getTitle(CompetitionConfig $competitionConfig): string
    {
        $competition = $competitionConfig->getSourceCompetition();
        $leagueName = mb_substr($competition->getLeague()->getName(), 0, DrawHelper::MaxLeagueNameChars);
        $season = $competition->getSeason();
        $title = 'competitionConfig: ' . $leagueName . ' ' . $season->getName();
        $title .= ', season => ' . $season->getPeriod()->toIso80000(DrawHelper::DateFormat);
        return $title;
    }

    protected function getGrid(CompetitionConfig $competitionConfig, int $titleLength): Grid
    {
        $width = $this->rangeCalculator->getMaxWidth($titleLength);
        $height = $this->rangeCalculator->getMaxHeight($competitionConfig);
        return new Grid($height, $width, $this->logger);
    }
}
