<?php

namespace SuperElf\GameRound;

use Sports\Game\State;
use SuperElf\Period;

/**
 * @psalm-suppress ClassMustBeFinal
 */
readonly class GameRoundShell
{
    public int $totalNrOfGames;
    public State $state;

    public function __construct(
        public int $number,
        public Period $period,
        public int $created,
        public int $inProgress,
        public int $finished,
        public int $canceled = 0
    ) {
        // canceled games count towards the total but never as finished, so progress stays visibly incomplete
        $this->totalNrOfGames = $created + $inProgress + $finished + $canceled;
        if ($inProgress > 0 || ($created > 0 && $finished > 0)) {
            $this->state = State::InProgress;
        } else if ($finished > 0) {
            $this->state = State::Finished;
        } else {
            $this->state = State::Created;
        }
    }
}
