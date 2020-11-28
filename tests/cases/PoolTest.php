<?php

declare(strict_types=1);

namespace SuperElf\Tests;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Association;
use Sports\Competition;
use Sports\League;
use Sports\Season;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Pool;
use SuperElf\PoolCollection;
use SuperElf\Period\View as ViewPeriod;

class PoolTest extends \PHPUnit\Framework\TestCase
{
    public function testWinnersOrLosersDescription()
    {
        $season = new Season( "20/21",
                              new Period( new DateTimeImmutable(), (new DateTimeImmutable())->modify("+1 year") ) );
        $sourceCompetition = new Competition( new League( new Association("KNVB"), "eredivisie"), $season );
        $name = "Kamp Duim";

        $viewPeriod = new ViewPeriod( $sourceCompetition, new Period( (new DateTimeImmutable())->modify("-1 days"), new DateTimeImmutable()));
        $assemblePeriod = new AssemblePeriod(
            $sourceCompetition,
            new Period( (new DateTimeImmutable())->modify("-1 days"), new DateTimeImmutable()),
            $viewPeriod);
        $transferPeriod = new TransferPeriod(
            $sourceCompetition,
            new Period( new DateTimeImmutable(), (new DateTimeImmutable())->modify("+1 days")),
            $viewPeriod,
        2);
        $pool = new Pool( new PoolCollection( new Association( $name ) ), $sourceCompetition,
                          $viewPeriod, $assemblePeriod, $transferPeriod );

        self::assertSame($name, $pool->getCollection()->getName());
    }
}
