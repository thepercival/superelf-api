<?php
declare(strict_types=1);

namespace SuperElf\Formation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Sports\Person;
use SportsHelpers\Identifiable;
use SuperElf\Formation as FormationBase;
use SuperElf\GameRound;
use SuperElf\Player as S11Player;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Substitute\Appearance;

class Line extends Identifiable
{
    /**
     * @var ArrayCollection<int|string, FormationPlace>|PersistentCollection<int|string, FormationPlace>
     * @psalm-var ArrayCollection<int|string, FormationPlace>
     */
    protected ArrayCollection|PersistentCollection $places;
    /**
     * @var ArrayCollection<int|string, Appearance>|PersistentCollection<int|string, Appearance>
     * @psalm-var ArrayCollection<int|string, Appearance>
     */
    protected ArrayCollection|PersistentCollection $substituteAppearances;

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

    /**
     * @return ArrayCollection<int|string, FormationPlace>|PersistentCollection<int|string, FormationPlace>
     * @psalm-return ArrayCollection<int|string, FormationPlace>
     */
    public function getPlaces(): ArrayCollection|PersistentCollection
    {
        return $this->places;
    }

    /**
     * @return ArrayCollection<int|string, FormationPlace>
     * @psalm-return ArrayCollection<int|string, FormationPlace>
     */
    public function getStartingPlaces(): ArrayCollection
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
     * @return ArrayCollection<int|string, Appearance>|PersistentCollection<int|string, Appearance>
     * @psalm-return ArrayCollection<int|string, Appearance>
     */
    public function getSubstituteAppearances(): ArrayCollection|PersistentCollection
    {
        return $this->substituteAppearances;
    }

    public function getAppareance(GameRound $gameRound): Appearance|null
    {
        $filtered = $this->substituteAppearances->filter(function (Appearance $appareance) use ($gameRound): bool {
            return $appareance->getGameRound() === $gameRound;
        });
        $firstAppareance = $filtered->first();
        return $firstAppareance === false ? null : $firstAppareance;
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
}
