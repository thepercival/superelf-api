<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Person;
use Sports\Sport\FootballLine;
use Sports\Team;
use Sports\Team\Player;
use Sports\Formation as SportsFormation;
use Sports\Formation\Line as SportsFormationLine;
use SportsHelpers\Identifiable;
use SuperElf\Achievement\BadgeCategory;
use SuperElf\Formation\Line;
use SuperElf\Formation\Place;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\Player as S11Player;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Formation extends Identifiable
{
    /**
     * @var Collection<int|string, Line>
     */
    protected Collection $lines;

    public function __construct(protected ViewPeriod $viewPeriod)
    {
        $this->lines = new ArrayCollection();
    }

    public function getViewPeriod(): ViewPeriod
    {
        return $this->viewPeriod;
    }

    /**
     * @return Collection<int|string, Line>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function getLine(int $lineNumber): Line
    {
        $filtered = $this->lines->filter(function (Line $line) use ($lineNumber): bool {
            return $line->getNumber() === $lineNumber;
        });
        $firstLine = $filtered->first();
        if ($firstLine === false) {
            throw new \Exception('the line "' . $lineNumber . '" could not be found', E_ERROR);
        }
        return $firstLine;
    }

    public function getName(): string
    {
        return implode("-", array_map(function (Line $line): int {
            return $line->getStartingPlaces()->count();
        }, $this->getLines()->toArray()));
    }

    /**
     * @return list<Place>
     */
    public function getPlaces(): array
    {
        $places = [];
        foreach ($this->lines as $line) {
            $places = array_merge($places, $line->getPlaces()->toArray() );
        }
        return array_values($places);
    }

    public function getPlace(FootballLine $lineNumber, int $placeNumber): Place {
        return $this->getLine($lineNumber->value)->getPlace($placeNumber);
    }

    public function getNrOfPersons(): int
    {
        $nrOfPersons = 0;
        foreach ($this->getLines() as $line) {
            $nrOfPersons += count($line->getAllPersons());
        }
        return $nrOfPersons;
    }

    /**
     * @return list<Person>
     */
    public function getPersons(): array
    {
        $persons = [];
        foreach ($this->lines as $line) {
            $persons = array_merge($persons, $line->getAllPersons());
        }
        return $persons;
    }

    public function getPerson(Team $team, \DateTimeImmutable $date = null): ?Person
    {
        if ($date === null) {
            $date = new \DateTimeImmutable();
        }
        $filtered = array_filter($this->getPersons(), function (Person $person) use ($team, $date): bool {
            return $person->getPlayer($team, $date) !== null;
        });
        $firstPerson = reset($filtered);
        return $firstPerson === false ? null : $firstPerson;
    }

    public function getPlayer(Person $person, \DateTimeImmutable $dateTime = null): ?Player
    {
        $checkDateTime = $dateTime !== null ? $dateTime : new \DateTimeImmutable();
        $filtered = $person->getPlayers()->filter(function (Player $player) use ($checkDateTime): bool {
            return $player->getPeriod()->contains($checkDateTime);
        });
        $firstPlayer = $filtered->first();
        return $firstPlayer === false ? null : $firstPlayer;
    }

    public function convertToBase(): SportsFormation {
        $formation = new SportsFormation();
        foreach( $this->getLines() as $s11Line ) {
            new SportsFormationLine($formation, $s11Line->getNumber(), count($s11Line->getStartingPlaces()) );
        }
        return $formation;
    }

    public function allPlacesHaveAPlayer(): bool
    {
        foreach ($this->getLines() as $line) {
            foreach ($line->getPlaces() as $place) {
                if ($place->getPlayer() === null) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return list<S11Player>
     */
    public function getPlayers(): array {
        $players = [];
        foreach( $this->lines as $line) {
            $players = array_merge( $players, $line->getPlayers(true));
        }
        return $players;
    }

    public function getPoints(GameRound $gameRound, Points $s11Points, BadgeCategory|null $badgeCategory): int
    {
        $points = 0;
        foreach ($this->getLines() as $line) {
            $points += $line->getPoints($gameRound, $s11Points, $badgeCategory);
        }
        return $points;
    }

    public function getTotalPoints(Points $s11Points, BadgeCategory|null $badgeCategory): int
    {
        $points = 0;
        foreach( $this->getLines() as $formationLine) {
            $points += $formationLine->getTotalPoints($s11Points, $badgeCategory);
        }
        return $points;
    }

    /**
     * @return list<Player>
     */
    public function getTeamPlayers(): array {
        $players = array_map(function(S11Player $s11Player): Player|null {
            return $s11Player->getMostRecentPlayer();
        }, $this->getPlayers());

        return array_values(array_filter($players, function(Player|null $player): bool {
            return $player !== null;
        }));
    }
}
