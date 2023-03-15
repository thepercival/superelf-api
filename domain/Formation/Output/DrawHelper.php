<?php

declare(strict_types=1);

namespace SuperElf\Formation\Output;

use Sports\Game\Against as AgainstGame;
use Sports\Output\Coordinate;
use Sports\Output\Grid\Align;
use Sports\Output\Grid\Drawer;
use Sports\Sport\FootballLine;
use SportsHelpers\Output\Color;
use SuperElf\Achievement\BadgeCategory;
use SuperElf\Formation;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\OneTeamSimultaneous;
use SuperElf\Periods\ViewPeriod;
use SuperElf\Points;

final class DrawHelper
{
    protected RangeCalculator $rangeCalculator;

    public function __construct(protected Drawer $drawer, protected Points $points)
    {
        $this->rangeCalculator = new RangeCalculator();
    }

    public function draw(Formation $formation, Coordinate $origin, string $title, BadgeCategory|null $badgeCategory): void {
        $viewPeriod = $formation->getViewPeriod();
        $coordinate = $this->drawTitle($title, $viewPeriod, $badgeCategory, $origin);
        $coordinate = $this->drawGameRounds($viewPeriod, $coordinate);
        foreach( $formation->getLines() as $formationLine ) {
            foreach( $formationLine->getStartingPlaces() as $formationPlace ) {
                $coordinate = $this->drawFormationPlace($formationPlace, $viewPeriod, $badgeCategory, $coordinate);
            }
            $coordinate = $this->drawFormationPlace($formationLine->getSubstitute(), $viewPeriod, $badgeCategory, $coordinate);
        }
        $this->drawTotals($formation, $badgeCategory, $coordinate);
    }

    public function drawTitle(string $title, ViewPeriod $viewPeriod, BadgeCategory|null $badgeCategory, Coordinate $origin): Coordinate
    {
        $badgeWidth = $this->rangeCalculator->getLineWidth() + RangeCalculator::BORDER
            + $this->rangeCalculator->getPlaceNrWidth() + RangeCalculator::BORDER
            + $this->rangeCalculator->getPersonNameWidth() + RangeCalculator::BORDER
            + $this->rangeCalculator->getTeamAbbrWidth();

        $badgeName = $badgeCategory !== null ? 'badge: ' . $badgeCategory->value : '';
        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            $badgeName,
            $badgeWidth,
            Align::Left,
            Color::Green
        )->incrementX();


        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $title,
            $this->rangeCalculator->getGameRoundsWidth($viewPeriod) - RangeCalculator::BORDER,
            Align::Center,
            Color::Green
        )->incrementX();

        $this->drawBorder($coordinate);

        return $origin->incrementY();
    }

    public function drawGameRounds(ViewPeriod $viewPeriod, Coordinate $origin): Coordinate
    {
        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            '',
            $this->rangeCalculator->getLineWidth(),
            Align::Right
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            '',
            $this->rangeCalculator->getPlaceNrWidth(),
            Align::Right
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            '',
            $this->rangeCalculator->getPersonNameWidth(),
            Align::Left
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            '',
            $this->rangeCalculator->getTeamAbbrWidth(),
            Align::Left
        )->incrementX();

        foreach( $viewPeriod->getGameRounds() as $gameRound) {
            $coordinate = $this->drawBorder($coordinate);
            $coordinate = $this->drawer->drawCellToRight(
                $coordinate,
                '' . $gameRound->getNumber(),
                $this->rangeCalculator->getGameRoundWidth(),
                Align::Right
            )->incrementX();
        }

        $coordinate = $this->drawBorder($coordinate);
        $this->drawer->drawCellToRight(
            $coordinate,
            'tot',
            $this->rangeCalculator->getTotalsWidth(),
            Align::Right
        );

        return $origin->incrementY();
    }

    public function drawFormationPlace(FormationPlace $formationPlace, ViewPeriod $viewPeriod, BadgeCategory|null $badgeCateogory, Coordinate $origin): Coordinate
    {
        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            FootballLine::getFirstChar($formationPlace->getLine()),
            $this->rangeCalculator->getLineWidth(),
            Align::Right
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $formationPlace->getNumber() === 0 ? 'R' : '' . $formationPlace->getNumber(),
            $this->rangeCalculator->getPlaceNrWidth(),
            Align::Right,
            $formationPlace->getNumber() === 0 ? Color::Yellow : null
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $this->getPersonName($formationPlace),
            $this->rangeCalculator->getPersonNameWidth(),
            Align::Left
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            $this->getTeamAbbr($formationPlace, $viewPeriod->getStartDateTime()),
            $this->rangeCalculator->getTeamAbbrWidth(),
            Align::Left
        )->incrementX();

        $formationLine = $formationPlace->getFormationLine();
        $isSub = $formationLine->getSubstitute() === $formationPlace;

        foreach( $viewPeriod->getGameRounds() as $gameRound) {
            $coordinate = $this->drawBorder($coordinate);

            $color = null;
            $points = '';
            if( !$isSub || $formationLine->getSubstituteAppareance($gameRound) !== null ) {
                $gameRoundStats = $formationPlace->getGameRoundStatistics($gameRound);
                if( $gameRoundStats !== null ) {
                    if( $gameRoundStats->hasAppeared() ) {
                        $points .= $formationPlace->getPoints($gameRound, $this->points, $badgeCateogory);
                    } else {
                        $points .= 'X';
                        $color = Color::Red;
                    }
                }

            }
            $coordinate = $this->drawer->drawCellToRight(
                $coordinate,
                $points,
                $this->rangeCalculator->getGameRoundWidth(),
                Align::Right,
                $color
            )->incrementX();
        }

        $coordinate = $this->drawBorder($coordinate);
        $this->drawer->drawCellToRight(
            $coordinate,
            '' . $formationPlace->getTotals()->getPoints($formationPlace->getLine(), $this->points, $badgeCateogory),
            $this->rangeCalculator->getTotalsWidth(),
            Align::Right,
            $formationPlace->getNumber() === 0 ? Color::Yellow : Color::Blue
        );

        return $origin->incrementY();
    }

    public function drawTotals(Formation $formation, BadgeCategory|null $badgeCategory, Coordinate $origin): Coordinate
    {
        $viewPeriod = $formation->getViewPeriod();

        $coordinate = $this->drawer->drawCellToRight(
            $origin,
            '',
            $this->rangeCalculator->getLineWidth(),
            Align::Right
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            '',
            $this->rangeCalculator->getPlaceNrWidth(),
            Align::Right
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            '',
            $this->rangeCalculator->getPersonNameWidth(),
            Align::Left
        )->incrementX();
        $coordinate = $this->drawBorder($coordinate);
        $coordinate = $this->drawer->drawCellToRight(
            $coordinate,
            '',
            $this->rangeCalculator->getTeamAbbrWidth(),
            Align::Left
        )->incrementX();

        $totalPoints = 0;
        foreach( $viewPeriod->getGameRounds() as $gameRound) {
            $coordinate = $this->drawBorder($coordinate);
            $gameRoundPoints = $formation->getPoints($gameRound, $this->points, $badgeCategory);
            $totalPoints += $gameRoundPoints;
            $coordinate = $this->drawer->drawCellToRight(
                $coordinate,
                '' . $gameRoundPoints,
                $this->rangeCalculator->getGameRoundWidth(),
                Align::Right,
                Color::Blue
            )->incrementX();
        }

        $coordinate = $this->drawBorder($coordinate);
        $this->drawer->drawCellToRight(
            $coordinate,
            '' . $totalPoints,
            $this->rangeCalculator->getTotalsWidth(),
            Align::Right,
            Color::Blue
        );

        return $origin->incrementY();
    }

    public function drawBorder(Coordinate $coordinate): Coordinate
    {
        return $this->drawer->drawToRight($coordinate, '|')->incrementX();
    }

    private function getPersonName(FormationPlace $formationPlace): string {
        $s11Player = $formationPlace->getPlayer();
        if( $s11Player === null ) {
            return '';
        }
        return $s11Player->getPerson()->getName(true);
    }

    private function getTeamAbbr(FormationPlace $formationPlace, \DateTimeImmutable $dateTime): string {
        $s11Player = $formationPlace->getPlayer();
        if( $s11Player === null ) {
            return '';
        }
        $oneTeamSim = new OneTeamSimultaneous();
        $teamPlayer = $oneTeamSim->getPlayer($s11Player->getPerson(), $dateTime);
        return $teamPlayer !== null ? (string)$teamPlayer->getTeam()->getAbbreviation() : '';
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
}
