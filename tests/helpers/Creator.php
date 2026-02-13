<?php

declare(strict_types=1);

namespace SuperElf\TestHelpers;

use DateTimeImmutable;
use League\Period\Period as LeaguePeriod;
use Sports\Association;
use Sports\Competition;
use Sports\League;
use Sports\Season;
use Sports\Sport;
use SportsHelpers\GameMode;
use SuperElf\CompetitionConfig;
use SuperElf\Defaults;
use SuperElf\Periods\AssemblePeriod;
use SuperElf\Periods\TransferPeriod;
use SuperElf\Periods\ViewPeriod;
use SuperElf\Points;
use SuperElf\League as S11League;
use SuperElf\Sport\Administrator;

trait Creator
{
    public function createSport(
        S11League $s11League,
        bool $teamSport = false
    ): Sport
    {
        $gameMode = $s11League === S11League::Competition ? GameMode::AllInOneGame : GameMode::Against;
        return new Sport(
            Administrator::SportName, $teamSport, $gameMode, 1
        );
    }

    public function createSourceCompetition(Season $season): Competition
    {
        return new Competition(new League(new Association('KNVB'), 'eredivisie'), $season);
    }

    public function createCompetitionConfig(
        Competition $sourceCompetition,
        Points|null $points = null,
        ViewPeriod|null $createAndJoinPeriod = null,
        AssemblePeriod|null $assemblePeriod = null,
        TransferPeriod|null $transferPeriod = null

    ): CompetitionConfig {
        if( $createAndJoinPeriod === null ) {
            $createAndJoinPeriod = new ViewPeriod(LeaguePeriod::fromDate(
                (new DateTimeImmutable())->modify("-2 days"), new DateTimeImmutable())
            );
        }
        if( $assemblePeriod === null ) {
            $assemblePeriod = LeaguePeriod::fromDate(
                $createAndJoinPeriod->getStartDateTime()->add(new \DateInterval('P1D')),
                $createAndJoinPeriod->getEndDateTime());
            $assemblePeriod = new AssemblePeriod(
                $assemblePeriod,
                new ViewPeriod(
                    LeaguePeriod::fromDate(
                        $assemblePeriod->endDate,
                        $assemblePeriod->endDate->add(new \DateInterval('P1D'))))
            );
        }
        if( $transferPeriod === null ) {
            $transferPeriod = LeaguePeriod::fromDate(
                $assemblePeriod->getViewPeriod()->getEndDateTime(),
                $assemblePeriod->getViewPeriod()->getEndDateTime()->add(new \DateInterval('P1D')));
            $transferPeriod = new TransferPeriod(
                $transferPeriod,
                new ViewPeriod(
                    LeaguePeriod::fromDate(
                        $transferPeriod->endDate,
                        $transferPeriod->endDate->add(new \DateInterval('P1D')))),
                2
            );
        }
        if( $points === null ) {
            $points = new Points(
                Defaults::POINTS_WIN,
                Defaults::POINTS_DRAW,
                Defaults::GOAL_GOALKEEPER,
                Defaults::GOAL_DEFENDER,
                Defaults::GOAL_MIDFIELDER,
                Defaults::GOAL_FORWARD,
                Defaults::ASSIST_GOALKEEPER,
                Defaults::ASSIST_DEFENDER,
                Defaults::ASSIST_MIDFIELDER,
                Defaults::ASSIST_FORWARD,
                Defaults::GOAL_PENALTY,
                Defaults::GOAL_OWN,
                Defaults::CLEAN_SHEET_GOALKEEPER,
                Defaults::CLEAN_SHEET_DEFENDER,
                Defaults::SPOTTY_SHEET_GOALKEEPER,
                Defaults::SPOTTY_SHEET_DEFENDER,
                Defaults::CARD_YELLOW,
                Defaults::CARD_RED
            );
        }


        return new CompetitionConfig(
            $sourceCompetition,
            $points,
            $createAndJoinPeriod,
            $assemblePeriod,
            $transferPeriod
        );
    }
}
