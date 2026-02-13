<?php

declare(strict_types=1);

namespace SuperElf\GameRound;

use League\Period\Period as LeaguePeriod;
use Sports\Game\Against as AgainstGame;
use SuperElf\GameRound;

final class PeriodCalculator
{
    public function __construct()
    {
    }

    /**
     * @param GameRound $gameRound
     * @param list<AgainstGame> $againstGames
     * @return LeaguePeriod|null
     */
    public function getGameRoundPeriod(GameRound $gameRound, array $againstGames): LeaguePeriod|null
    {
        $start = null;
        $end = null;
        foreach ($againstGames as $againstGame) {
            if ($gameRound->getNumber() !== $againstGame->getGameRoundNumber()) {
                continue;
            }
            $gameDateTime = $againstGame->getStartDateTime();
            if ($start === null || $end === null) {
                $start = $gameDateTime;
                $end = $gameDateTime;
                continue;
            }
            if ($gameDateTime < $start) {
                $start = $gameDateTime;
            }
            if ($gameDateTime > $end) {
                $end = $gameDateTime;
            }
        }
        if ($start === null || $end === null) {
            return null;
        }
        return LeaguePeriod::fromDate($start, $end);
    }
}
