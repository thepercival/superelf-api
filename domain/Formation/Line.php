<?php

namespace SuperElf\Formation;

use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Formation as FormationBase;
use Sports\Person;

class Line
{
    /**
     * @var int
     */
    protected $id;
    protected int $number;
    protected int $maxNrOfPersons;
    protected FormationBase $formation;
    /**
     * @var ArrayCollection | Person[]
     */
    protected $persons;
    /**
     * @var Person|null
     */
    protected $substitute;

    public function __construct(FormationBase $formation, int $number, int $maxNrOfPersons)
    {
        $this->setFormation($formation);
        $this->number = $number;
        $this->maxNrOfPersons = $maxNrOfPersons;
        $this->persons = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getFormation(): FormationBase
    {
        return $this->formation;
    }

    /**
     * @param FormationBase $formation
     */
    protected function setFormation(FormationBase $formation)
    {
        if (!$formation->getLines()->contains($this)) {
            $formation->getLines()->add($this) ;
        }
        $this->formation = $formation;
    }

    public function getNumber(): int
    {
        return $this->number;
    }


    public function getMaxNrOfPersons()
    {
        return $this->maxNrOfPersons;
    }

    /**
     * @return ArrayCollection|Person[]
     */
    public function getPersons()
    {
        return $this->persons;
    }

    public function getSubstitute(): ?Person
    {
        return $this->substitute;
    }

    public function setSubstitute( Person $substitute = null )
    {
        $this->substitute = $substitute;
    }

    /**
     * @return array|Person[]
     */
    public function getAllPersons(): array
    {
        $persons = $this->getPersons()->toArray();
        if( $this->substitute !== null ) {
            return array_merge( $persons, [$this->substitute] );
        }
        return $persons;
    }
}
