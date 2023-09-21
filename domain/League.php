<?php

declare(strict_types=1);

namespace SuperElf;

enum League
{
    case Competition;
    case Cup;
    case SuperCup;
    case WorldCup;

    public static function from(string $name): self
    {
        switch ($name) {
            case self::Competition->name:
                return self::Competition;
            case self::Cup->name:
                return self::Cup;
            case self::SuperCup->name:
                return self::SuperCup;
            case self::WorldCup->name:
                return self::WorldCup;
            default:
                throw new \Exception('unknown leaguename', E_ERROR);
        }
    }
}
