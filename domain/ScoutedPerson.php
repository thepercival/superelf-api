<?php

namespace SuperElf;

use Sports\Competition;
use Sports\Person;
use Sports\Season;

class ScoutedPerson {

    protected int $id;
    protected User $user;
    protected Competition $sourceCompetition;
    protected Person $person;
    protected int $nrOfStars;

    public function __construct(User $user, Competition $sourceCompetition, Person $person, int $nrOfStars )
    {
        $this->user = $user;
        $this->sourceCompetition = $sourceCompetition;
        $this->person = $person;
        $this->nrOfStars = $nrOfStars;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function getSourceCompetition(): Competition {
        return $this->sourceCompetition;
    }

    public function getPerson(): Person {
        return $this->person;
    }

    public function getNrOfStars(): int {
        return $this->nrOfStars;
    }

    public function setNrOfStars(int $nrOfStars) {
        $this->nrOfStars = $nrOfStars;
    }
}