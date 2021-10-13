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
use SuperElf\Substitute\Appearance;

class Line extends Identifiable
{
    /**
     * @var ArrayCollection<int|string, S11Player>|PersistentCollection<int|string, S11Player>
     * @psalm-var ArrayCollection<int|string, S11Player>
     */
    protected ArrayCollection|PersistentCollection $players;
    protected S11Player|null $substitute = null;
    /**
     * @var ArrayCollection<int|string, Appearance>|PersistentCollection<int|string, Appearance>
     * @psalm-var ArrayCollection<int|string, Appearance>
     */
    protected ArrayCollection|PersistentCollection $substituteAppearances;

    public function __construct(
        protected FormationBase $formation,
        protected  int $number,
        protected  int $maxNrOfPersons
    )
    {
        $this->players = new ArrayCollection();
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


    public function getMaxNrOfPersons(): int
    {
        return $this->maxNrOfPersons;
    }

    /**
     * @return ArrayCollection<int|string, S11Player>|PersistentCollection<int|string, S11Player>
     * @psalm-return ArrayCollection<int|string, S11Player>
     */
    public function getPlayers(): ArrayCollection|PersistentCollection
    {
        return $this->players;
    }

    public function getSubstitute(): S11Player|null
    {
        return $this->substitute;
    }

    public function setSubstitute(S11Player $player = null): void
    {
        $this->substitute = $player;
    }

    /**
     * @return list<Person>
     */
    public function getAllPersons(): array
    {
        $persons = [];
        foreach ($this->getPlayers() as $player) {
            $persons[] = $player->getPerson();
        }
        $substitute = $this->getSubstitute();
        if ($substitute !== null) {
            $persons[] = $substitute->getPerson();
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
}
