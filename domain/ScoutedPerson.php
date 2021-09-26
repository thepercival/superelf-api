<?php
declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Person;
use Sports\Season;
use SportsHelpers\Identifiable;

class ScoutedPerson extends Identifiable {
    public function __construct(
        protected User $user,
        protected Competition $sourceCompetition,
        protected Person $person,
        protected int $nrOfStars )
    {
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

    public function setNrOfStars(int $nrOfStars): void {
        $this->nrOfStars = $nrOfStars;
    }
}