<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Person as BasePerson;
use Sports\Sport\FootballLine;
use Sports\Team;
use Sports\Team\Player as TeamPlayer;
use SportsHelpers\Identifiable;
use SuperElf\Achievement\BadgeCategory;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use League\Period\Period;
use SuperElf\Points\Calculator;

class Player extends Identifiable
{
    protected Totals $totals;
    protected int $totalPoints = 0;
    /**
     * @var Collection<int|string, Statistics>
     */
    protected Collection $statistics;

    public function __construct(
        protected ViewPeriod $viewPeriod,
        protected BasePerson $person,
        Totals|null $totals = null
    ) {
        $this->statistics = new ArrayCollection();
        if ($totals === null) {
            $totals = new Totals();
        }
        $this->totals = $totals;
    }

    public function getViewPeriod(): ViewPeriod
    {
        return $this->viewPeriod;
    }

    public function getPerson(): BasePerson
    {
        return $this->person;
    }

    public function getLine(): FootballLine
    {
        $player = $this->getPlayers()->first();
        if (!($player instanceof TeamPlayer)) {
            throw new \Exception('s11player should always have a line', E_ERROR);
        }
        return FootballLine::from($player->getLine());
    }

    /**
     * @return Collection<int|string, Statistics>
     */
    public function getStatistics(): Collection
    {
        return $this->statistics;
    }

    public function getGameRoundStatistics(GameRound $gameRound): Statistics|null
    {
        $filtered = $this->getStatistics()->filter(function (Statistics $statistics) use ($gameRound): bool {
            return $statistics->getGameRound() === $gameRound;
        });
        $firstScore = $filtered->first();
        return $firstScore === false ? null : $firstScore;
    }

    public function getTotals(): Totals
    {
        return $this->totals;
    }

    public function setTotals(Totals $totals): void
    {
        $this->totals = $totals;
    }

    public function getTotalPoints(): int
    {
        return $this->totalPoints;
    }

    public function setTotalPoints(int $totalPoints): void
    {
        $this->totalPoints = $totalPoints;
    }

    public function getPoints(GameRound $gameRound, Points $s11Points, FootballLine $line, BadgeCategory|null $badgeCategory): int
    {
        $statistics = $this->getGameRoundStatistics($gameRound);
        if ($statistics === null) {
            return 0;
        }
        return $statistics->getPoints($line, $s11Points, $badgeCategory);
    }

    /**
     * @return Collection<int|string, TeamPlayer>
     */
    public function getPlayers(): Collection
    {
        return $this->getPerson()->getPlayers(null, $this->getViewPeriod()->getPeriod());
    }

    /**
     * @param Team|null $team
     * @param Period|null $period
     * @param int|null $line
     * @return list<TeamPlayer>
     */
    public function getPlayersDescendingStart(Team|null $team = null, Period|null $period = null, int|null $line = null): array {
        $players = $this->getPerson()->getPlayers($team, $period, $line)->toArray();
        uasort( $players, function(TeamPlayer $plA, TeamPlayer $plB): int {
            return $plB->getStartDateTime()->getTimestamp() - $plA->getStartDateTime()->getTimestamp();
        });
        return array_values($players);
    }

    public function getMostRecentPlayer(): TeamPlayer|null {
        $players = $this->getPlayersDescendingStart();
        return array_shift($players);
    }
}
