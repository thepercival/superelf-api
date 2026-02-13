<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Psr\Log\LoggerInterface;
use Sports\Output\Coordinate;
use Sports\Output\Grid;
use Sports\Output\Grid\Drawer as GridDrawer;
use SportsHelpers\Output\OutputAbstract;
use SuperElf\Achievement\BadgeCategory;
use SuperElf\Formation\Output\DrawHelper;
use SuperElf\Formation\Output\RangeCalculator;
use SuperElf\Formation;
use SuperElf\Points;

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

    public function output(Points $points, Formation $formation, BadgeCategory|null $badgeCategory): void
    {
        $title = $this->getTitle($formation);
        $grid = $this->getGrid($formation);
        $drawer = new GridDrawer($grid);
        $coordinate = new Coordinate(0, 0);
        $drawHelper = new DrawHelper($drawer, $points);
        $drawHelper->draw($formation, $coordinate, $title, $badgeCategory);

//        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
//        $retVal = 'batch ' . ($batchNr < 10 ? ' ' : '') . $batchNr;
//        return $this->outputColor($batchColor, $retVal);
        $grid->output();
    }

    public function getTitle(Formation $formation): string
    {
        $title = $formation->convertToBase()->getName();
        $title .= ' ' . $formation->getViewPeriod()->getPeriod()->toIso80000('Y-m-d');
        return $title;
    }

    protected function getGrid(Formation $formation): Grid
    {
        $rangeCalculator = new RangeCalculator();
        return new Grid(
            $rangeCalculator->getHeight(),
            $rangeCalculator->getWidth($formation->getViewPeriod()),
            $this->logger
        );
    }
}
