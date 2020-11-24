<?php

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Person;
use Sports\Team;
use SuperElf\Formation\Line;

class Formation
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var ArrayCollection|Line[]
     */
    protected $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return ArrayCollection|Line[]
     */
    public function getLines()
    {
        return $this->lines;
    }

    public function getLine( int $lineNumber ): ?Line
    {
        $filtered = $this->lines->filter( function( Line $line ) use ($lineNumber): bool {
            return $line->getNumber() === $lineNumber;
        });
        return $filtered->count() > 0 ? $filtered->first() : 0;
    }

    public function getName(): string {
        return implode( "-", $this->getLines()->map( function( Line $line ): int {
            return $line->getMaxNrOfPersons();
        })->toArray() );
    }

    public function getNrOfPersons(): int {
        $nrOfPersons = 0;
        foreach( $this->getLines() as $line ) {
            $nrOfPersons += $line->getPersons()->count();
            if( $line->getSubstitute() !== null ) {
                $nrOfPersons++;
            }
        }
        return $nrOfPersons;
    }

    /**
     * @return array | Person[]
     */
    public function getPersons(): array {
        $persons = [];
        foreach( $this->lines as $line ) {
            $persons = array_merge( $persons, $line->getAllPersons() );
        }
        return $persons;
    }

    public function getPerson(Team $team, \DateTimeImmutable $date = null): ?Person {
        $filtered = array_filter( $this->getPersons(), function(Person $person) use ($team, $date): bool {
            return $person->getPlayer($team, $date) !== null;
        });
        return count($filtered) > 0 ? reset($filtered) : null;
    }
}
