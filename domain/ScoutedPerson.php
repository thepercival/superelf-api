<?php

namespace SuperElf;

use Sports\Person;
use Sports\Season;

class ScoutedPerson {

    protected int $id;
    protected User $user;
    protected Season $season;
    protected Person $person;
    protected int $nrOfStars;

    public function __construct(User $user, Season $season, Person $person, int $nrOfStars )
    {
        $this->user = $user;
        $this->season = $season;
        $this->person = $person;
        $this->nrOfStars = $nrOfStars;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function getSeason(): Season {
        return $this->season;
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