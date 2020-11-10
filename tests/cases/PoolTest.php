<?php

declare(strict_types=1);

namespace SuperElf\Tests;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Association;
use Sports\Competition;
use Sports\League;
use Sports\Season;
use SuperElf\Pool;
use SuperElf\PoolCollection;

class PoolTest extends \PHPUnit\Framework\TestCase
{
    public function testWinnersOrLosersDescription()
    {
        $season = new Season( "20/21",
                              new Period( new DateTimeImmutable(), (new DateTimeImmutable())->modify("+1 year") ) );
        $sourceCompetition = new Competition( new League( new Association("KNVB"), "eredivisie"), $season );
        $name = "Kamp Duim";

        $pool = new Pool( new PoolCollection( new Association( $name ) ), $season, $sourceCompetition );

        self::assertSame($name, $pool->getCollection()->getName());
    }
}
