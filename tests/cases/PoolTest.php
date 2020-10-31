<?php

declare(strict_types=1);

namespace SuperElf\Tests;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Season;
use SuperElf\Pool;
use SuperElf\PoolCollection;

class PoolTest extends \PHPUnit\Framework\TestCase
{
    public function testWinnersOrLosersDescription()
    {
        $name = "Kamp Duim";
        $season = new Season( "20/21",
            new Period( new DateTimeImmutable(), (new DateTimeImmutable())->modify("+1 year") ) );
        $pool = new Pool( new PoolCollection( $name ), $season );

        self::assertSame($name, $pool->getName());
    }
}
