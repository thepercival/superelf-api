<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Person;
use Sports\Sport\FootballLine;
use SportsHelpers\Identifiable;
use SuperElf\Achievement\BadgeCategory;
use SuperElf\Formation as FormationBase;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Player as S11Player;
use SuperElf\GameRound;
use SuperElf\Points;
use SuperElf\Substitute\Appearance;
use SuperElf\Totals;

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
        $substituteAppearances = $this->substituteAppearances->toArray();
        return array_map(function (Appearance $appareance): int {
            return $appareance->getGameRound()->getNumber();
        }, $substituteAppearances);
    }


    public function getPoints(GameRound $gameRound, Points $s11Points, BadgeCategory|null $badgeCategory): int
    {
        $points = 0;
        foreach ($this->getStartingPlaces() as $place) {
            $points += $place->getPoints($gameRound, $s11Points, $badgeCategory);
        }
        $appaerance = $this->getSubstituteAppareance($gameRound);
        if ($appaerance !== null) {
            $points += $this->getSubstitute()->getPoints($gameRound, $s11Points, $badgeCategory);
        }
        return $points;
    }

    public function getTotals(): Totals
    {
        $totals = new Totals();
        foreach ($this->getPlaces() as $place) {
            $totals = $totals->add($place->getTotals());
        }
        return $totals;
    }

    public function getTotalPoints(Points $points, BadgeCategory|null $badgeCategory): int
    {
        return $this->getTotals()->getPoints($this->getLine(), $points, $badgeCategory);
    }

    /**
     * @param bool $withSubstitute
     * @return list<S11Player>
     */
    public function getPlayers(bool $withSubstitute): array {

        $places = $this->getStartingPlaces();
        if ($withSubstitute) {
            $places[] = $this->getSubstitute();
        }
        $players = [];
        foreach( $places as $place) {
            $player = $place->getPlayer();
            if ($player === null) {
                continue;
            }
            $players[] = $player;
        }
        return $players;
    }
}
