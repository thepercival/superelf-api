<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use DeprecationTests\Foo;
use Sports\Sport\FootballLine;
use SportsHelpers\Identifiable;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\GameRound;
use SuperElf\Player as S11Player;
use SuperElf\Points;
use SuperElf\Points\Calculator;
use SuperElf\Statistics;
use SuperElf\Totals;

class Place extends Identifiable
{
    protected int $number;
    protected int $penaltyPoints = 0;
    protected Totals|null $totals = null;
    protected int $totalPoints = 0;

    public function __construct(
        protected FormationLine $formationLine,
        protected S11player|null $player,
        int|null $number = null
    ) {
        if (!$this->formationLine->getPlaces()->contains($this)) {
            $this->formationLine->getPlaces()->add($this);
        }
        if ($number === null) {
            $number = $this->formationLine->getPlaces()->count() - 1; /* substitute */
        }
        $this->number = $number;
        $this->totals = new Totals();
    }

    public function getFormationLine(): FormationLine
    {
        return $this->formationLine;
    }

    public function getLine(): FootballLine
    {
        return $this->formationLine->getLine();
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPenaltyPoints(): int
    {
        return $this->penaltyPoints;
    }

    public function setPenaltyPoints(int $penaltyPoints): void
    {
        $this->penaltyPoints = $penaltyPoints;
    }

    public function getPlayer(): S11Player|null
    {
        return $this->player;
    }

    public function setPlayer(S11Player|null $player): void
    {
        $this->player = $player;
    }

    public function getTotals(): Totals
    {
        $totals = $this->totals;
        if ($totals === null) {
            $totals = new Totals();
            $this->totals = $totals;
        }
        return $totals;
    }

    public function setTotals(Totals $totals): void
    {
        $this->totals = $totals;
    }

    protected function getTotalPoints(): int
    {
        return $this->totalPoints;
    }

    public function setTotalPoints(int $totalPoints): void
    {
        $this->totalPoints = $totalPoints;
    }

    public function getTotalNettoPoints(): int
    {
        return $this->totalPoints - $this->penaltyPoints;
    }

    public function isSubstitute(): bool
    {
        return $this->getFormationLine()->getSubstitute() === $this;
    }

    /**
     * @return list<Statistics>
     */
    public function getStatistics(): array
    {
        $s11Player = $this->getPlayer();
        if ($s11Player === null) {
            return [];
        }

        if (!$this->isSubstitute()) {
            return array_values($s11Player->getStatistics()->toArray());
        }

        $formationLine = $this->getFormationLine();
        $gameRounds = $s11Player->getViewPeriod()->getGameRounds();

        $gameRoundsSubstitute = array_filter(
            $gameRounds->toArray(),
            function (GameRound $gameRound) use ($formationLine): bool {
                return $formationLine->getSubstituteAppareance($gameRound) !== null;
            }
        );

        $stats = array_map(function (GameRound $gameRound) use ($s11Player): Statistics|null {
            return $s11Player->getGameRoundStatistics($gameRound);
        }, $gameRoundsSubstitute);

        return array_values(
            array_filter(
                $stats,
                function (Statistics|null $statistics): bool {
                    return $statistics !== null;
                }
            )
        );
    }

    public function getGameRoundStatistics(GameRound $gameRound): Statistics|null
    {
        foreach ($this->getStatistics() as $statistics) {
            if ($statistics->getGameRound() === $gameRound) {
                return $statistics;
            }
        }
        return null;
    }

    public function getPoints(GameRound $gameRound, Points $s11Points): int
    {
        $player = $this->getPlayer();
        if ($player === null) {
            return 0;
        }
        $statistics = $player->getGameRoundStatistics($gameRound);
        if ($statistics === null) {
            return 0;
        }
        return (new Calculator())->getStatisticsPoints($this->getLine(), $statistics, $s11Points);
    }
}
