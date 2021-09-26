<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Sports\Person;
use SportsHelpers\Identifiable;
use SuperElf\Formation as FormationBase;
use SuperElf\GameRound;
use SuperElf\Period\View\Person as ViewPeriodPerson;
use SuperElf\Pool;
use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;

class Line extends Identifiable
{
    /**
     * @var ArrayCollection<int|string, ViewPeriodPerson>|PersistentCollection<int|string, ViewPeriodPerson>
     */
    protected ArrayCollection|PersistentCollection $viewPeriodPersons;
    protected PoolUserViewPeriodPerson|null $substitute = null;

    public function __construct(
        protected FormationBase $formation, protected  int $number, protected  int $maxNrOfPersons)
    {
        $this->viewPeriodPersons = new ArrayCollection();
    }

    public function getFormation(): FormationBase
    {
        return $this->formation;
    }

    public function getNumber(): int
    {
        return $this->number;
    }


    public function getMaxNrOfPersons(): int
    {
        return $this->maxNrOfPersons;
    }

    /**
     * @return ArrayCollection<int|string, ViewPeriodPerson>|PersistentCollection<int|string, ViewPeriodPerson>
     */
    public function getViewPeriodPersons(): ArrayCollection|PersistentCollection
    {
        return $this->viewPeriodPersons;
    }

    public function getSubstitute(): ?PoolUserViewPeriodPerson
    {
        return $this->substitute;
    }

    public function setSubstitute( PoolUserViewPeriodPerson $substitute = null ): void
    {
        $this->substitute = $substitute;
    }

    /**
     * @return list<Person>
     */
    public function getAllPersons(): array
    {
        $persons = [];
        foreach( $this->getViewPeriodPersons() as $viewPeriodPerson ) {
            $persons[] = $viewPeriodPerson->getPerson();
        }
        $substitute = $this->getSubstitute();
        if( $substitute !== null ) {
            $persons[] = $substitute->getViewPeriodPerson()->getPerson();
        }
        return $persons;
    }

    public function needSubstitute( GameRound $gameRound ): bool {
        foreach( $this->getViewPeriodPersons() as $viewPeriodPerson ) {
            $gameRoundScore = $viewPeriodPerson->getGameRoundScore($gameRound);
            if ($gameRoundScore !== null && !$gameRoundScore->participated()) {
                return true;
            }
        }
        return false;
    }
}
