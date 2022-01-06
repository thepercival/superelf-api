<?php

declare(strict_types=1);

namespace SuperElf\Tests;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Association;
use Sports\Competition;
use Sports\League;
use Sports\Season;
use SuperElf\Defaults;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Points;
use SuperElf\Pool;
use SuperElf\PoolCollection;

class PoolTest extends \PHPUnit\Framework\TestCase
{
    public function testWinnersOrLosersDescription(): void
    {
        $season = new Season(
            "20/21",
            new Period(new DateTimeImmutable(), (new DateTimeImmutable())->modify("+1 year"))
        );
        $sourceCompetition = new Competition(new League(new Association("KNVB"), "eredivisie"), $season);
        $name = "Kamp Duim";

        $viewPeriod = new ViewPeriod($sourceCompetition, new Period((new DateTimeImmutable())->modify("-1 days"), new DateTimeImmutable()));
        $assemblePeriod = new AssemblePeriod(
            $sourceCompetition,
            new Period((new DateTimeImmutable())->modify("-1 days"), new DateTimeImmutable()),
            $viewPeriod
        );
        $transferPeriod = new TransferPeriod(
            $sourceCompetition,
            new Period(new DateTimeImmutable(), (new DateTimeImmutable())->modify("+1 days")),
            $viewPeriod,
            2
        );
        $points = new Points(
            $season,
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
        $pool = new Pool(
            new PoolCollection(new Association($name)),
            $sourceCompetition,
            $points,
            $viewPeriod,
            $assemblePeriod,
            $transferPeriod
        );

        self::assertSame($name, $pool->getCollection()->getName());
    }
}
