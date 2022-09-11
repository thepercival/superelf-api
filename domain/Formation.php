<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Person;
use Sports\Team;
use Sports\Team\Player;
use SportsHelpers\Identifiable;
use SuperElf\Formation\Line;
use SuperElf\Periods\ViewPeriod as ViewPeriod;

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
            return $line->getPlaces()->count();
        }, $this->getLines()->toArray()));
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

    public function getPoints(GameRound $gameRound, Points $s11Points): int
    {
        $points = 0;
        foreach ($this->getLines() as $line) {
            $points += $line->getPoints($gameRound, $s11Points);
        }
        return $points;
    }
}
