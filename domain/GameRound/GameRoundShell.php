<?php

namespace SuperElf\GameRound;


use SuperElf\Period;

readonly class GameRoundShell
{

    public function __construct(
        public int $number,
        public Period $period,
        public int $created,
        public int $inProgress,
        public int $finished
    ) {
    }
}
