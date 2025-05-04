<?php

namespace SuperElf\GameRound;

use Sports\Game\State;
use SuperElf\Period;

readonly class GameRoundShell
{
    public int $totalNrOfGames;
    public State $state;

    public function __construct(
        public int $number,
        public Period $period,
        public int $created,
        public int $inProgress,
        public int $finished
    ) {
        $this->totalNrOfGames = $created + $inProgress + $finished;
        if( $inProgress > 0 || ( $created > 0 && $finished > 0 ) ) {
            $this->state = State::InProgress;
        } else if( $finished > 0) {
            $this->state = State::Finished;
        } else {
            $this->state = State::Created;
        }
    }
}
