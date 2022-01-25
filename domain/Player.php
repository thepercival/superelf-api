<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Person as BasePerson;
use Sports\Sport\FootballLine;
use Sports\Team\Player as TeamPlayer;
use SportsHelpers\Identifiable;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Player\Totals;

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
        $player = $this->getPerson()->getPlayers()->first();
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

    /**
     * @return Collection<int|string, TeamPlayer>
     */
    public function getPlayers(): Collection
    {
        return $this->getPerson()->getPlayers(null, $this->getViewPeriod()->getPeriod());
    }
}
