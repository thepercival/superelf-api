<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Person;
use Sports\Sport\FootballLine;
use SportsHelpers\Identifiable;
use SuperElf\Formation as FormationBase;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\GameRound;
use SuperElf\Points;
use SuperElf\Substitute\Appearance;

class Line extends Identifiable
{
    /**
     * @var Collection<int|string, FormationPlace>
     */
    protected Collection $places;
    /**
     * @var Collection<int|string, Appearance>
     */
    protected Collection $substituteAppearances;

    private const SUBSTITUTE_NUMBER = 0;

    public function __construct(protected FormationBase $formation, protected  int $number)
    {
        if (!$this->formation->getLines()->contains($this)) {
            $this->formation->getLines()->add($this) ;
        }
        $this->places = new ArrayCollection();
        $this->substituteAppearances = new ArrayCollection();
    }

    public function getFormation(): FormationBase
    {
        return $this->formation;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getLine(): FootballLine
    {
        return FootballLine::from($this->number);
    }

    /**
     * @return Collection<int|string, FormationPlace>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @return Collection<int|string, FormationPlace>
     */
    public function getStartingPlaces(): Collection
    {
        return $this->places->filter(function (FormationPlace $formationPlace): bool {
            return $formationPlace->getNumber() > self::SUBSTITUTE_NUMBER;
        });
    }

    public function getPlace(int $number): FormationPlace
    {
        $filtered = $this->places->filter(function (FormationPlace $formationPlace) use ($number): bool {
            return $formationPlace->getNumber() === $number;
        });
        $firstPlace = $filtered->first();
        if ($firstPlace === false) {
            throw new \Exception('the formation-place for number "' . $number . '" could not be found', E_ERROR);
        }
        return $firstPlace;
    }

    public function getSubstitute(): FormationPlace
    {
        return $this->getPlace(self::SUBSTITUTE_NUMBER);
    }

    /**
     * @return list<Person>
     */
    public function getAllPersons(): array
    {
        $persons = [];
        foreach ($this->getPlaces() as $formationPlace) {
            $s11Player = $formationPlace->getPlayer();
            if ($s11Player !== null) {
                $persons[] = $s11Player->getPerson();
            }
        }
        return $persons;
    }

//    public function needSubstitute( GameRound $gameRound ): bool {
//        foreach( $this->getPlayers() as $player ) {
//            $gameRoundScore = $player->getGameRoundScore($gameRound);
//            if ($gameRoundScore !== null && !$gameRoundScore->participated()) {
//                return true;
//            }
//        }
//        return false;
//    }

    /**
     * @return Collection<int|string, Appearance>
     */
    public function getSubstituteAppearances(): Collection
    {
        return $this->substituteAppearances;
    }

    public function getSubstituteAppareance(GameRound $gameRound): Appearance|null
    {
        foreach ($this->substituteAppearances as $substituteAppearance) {
            if ($substituteAppearance->getGameRound() === $gameRound) {
                return $substituteAppearance;
            }
        }
        return null;
    }

    /**
     * @return array<int|string, int>
     */
    public function getSubstituteAppearancesAsRoundNumbers(): array
    {
        return $this->substituteAppearances->map(function (Appearance $appareance): int {
            return $appareance->getGameRound()->getNumber();
        })->toArray();
    }


    public function getPoints(GameRound $gameRound, Points $s11Points): int
    {
        $points = 0;
        foreach ($this->getStartingPlaces() as $place) {
            $points += $place->getPoints($gameRound, $s11Points);
        }
        $appaerance = $this->getSubstituteAppareance($gameRound);
        if ($appaerance !== null) {
            $points += $this->getSubstitute()->getPoints($gameRound, $s11Points);
        }
        return $points;
    }


}
