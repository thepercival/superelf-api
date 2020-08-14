<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:27
 */

namespace SuperElf\Tests;

use SuperElf\Pool;

class PoolTest extends \PHPUnit\Framework\TestCase
{
    public function testWinnersOrLosersDescription()
    {
        $name = "Kamp Duim";
        $pool = new Pool($name);

        self::assertSame($name, $pool->getName());
    }
}
