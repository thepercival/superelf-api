<?php

namespace SuperElf\CompetitionPerson;

use SuperElf\CompetitionPerson;
use SuperElf\GameRound;
use SuperElf\GameRound\Score as BaseGameRoundScore;
use SuperElf\Pool;

class GameRoundScore extends BaseGameRoundScore {
    protected CompetitionPerson $competitionPerson;

    public function __construct(CompetitionPerson $competitionPerson, GameRound $gameRound )
    {
        parent::__construct($gameRound);
        $this->setCompetitionPerson( $competitionPerson );
    }

    public function getCompetitionPerson(): CompetitionPerson {
        return $this->competitionPerson;
    }

    protected function setCompetitionPerson(CompetitionPerson $competitionPerson)
    {
        if (!$competitionPerson->getGameRoundScores()->contains($this)) {
            $competitionPerson->getGameRoundScores()->add($this) ;
        }
        $this->competitionPerson = $competitionPerson;
    }
}