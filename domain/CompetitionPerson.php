<?php

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Competition;
use Sports\Person;
use SuperElf\CompetitionPerson\GameRoundScore;

class CompetitionPerson {
    public const SHEET_SPOTTY_THRESHOLD = 4;

    public const RESULT = 1;
    public const GOALS_FIELD = 2;
    public const GOALS_PENALTY = 4;
    public const GOALS_OWN = 8;
    public const ASSISTS = 16;
    public const SHEET_CLEAN = 32;
    public const SHEET_SPOTTY = 64;
    public const CARDS_YELLOW = 128;
    public const CARD_RED = 256;
    public const LINEUP = 512;
    public const SUBSTITUTED = 1024;
    public const SUBSTITUTE = 2048;
    public const LINE = 4096;

    protected int $id;
    protected Person $person;

    protected Competition $sourceCompetition;
    /**
     * @var ArrayCollection | GameRoundScore[]
     */
    protected $gameRoundScores;

    public function __construct(
        Competition $sourceCompetition, Person $person )
    {
        $this->sourceCompetition = $sourceCompetition;
        $this->person = $person;
        $this->gameRoundScores = new ArrayCollection();
    }

    public function getCourceCompetition(): Competition {
        return $this->sourceCompetition;
    }

    public function getPerson(): Person {
        return $this->person;
    }

    /**
     * @return ArrayCollection | GameRoundScore[]
     */
    public function getGameRoundScores() {
        return $this->gameRoundScores;
    }
}