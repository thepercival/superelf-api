<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Person;
use SuperElf\Formation as FormationBase;
use SuperElf\Period\View\Person as ViewPeriodPerson;
use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;

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
     * @var ArrayCollection | ViewPeriodPerson[]
     */
    protected $viewPeriodPersons;
    /**
     * @var PoolUserViewPeriodPerson|null
     */
    protected $substitute;

    public function __construct(FormationBase $formation, int $number, int $maxNrOfPersons)
    {
        $this->setFormation($formation);
        $this->number = $number;
        $this->maxNrOfPersons = $maxNrOfPersons;
        $this->viewPeriodPersons = new ArrayCollection();
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
     * @return ArrayCollection|ViewPeriodPerson[]
     */
    public function getViewPeriodPersons()
    {
        return $this->viewPeriodPersons;
    }

    public function getSubstitute(): ?PoolUserViewPeriodPerson
    {
        return $this->substitute;
    }

    public function setSubstitute( PoolUserViewPeriodPerson $substitute = null )
    {
        $this->substitute = $substitute;
    }

    /**
     * @return array|Person[]
     */
    public function getAllPersons(): array
    {
        $persons = [];
        foreach( $this->getViewPeriodPersons() as $viewPeriodPerson ) {
            $persons[] = $viewPeriodPerson->getPerson();
        }
        if( $this->getSubstitute() !== null ) {
            $persons[] = $this->getSubstitute()->getViewPeriodPerson()->getPerson();
        }
        return $persons;
    }
}
