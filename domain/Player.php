<?php
declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use Sports\Person as BasePerson;
use Sports\Team\Player as TeamPlayer;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Player\Totals;

class Player extends Identifiable
{
    protected int $totalPoints = 0;
    /**
     * @var ArrayCollection<int|string, Statistics>|PersistentCollection<int|string, Statistics>
     * @psalm-var ArrayCollection<int|string, Statistics>
     */
    protected ArrayCollection|PersistentCollection $statistics;

    public function __construct(
        protected ViewPeriod $viewPeriod,
        protected BasePerson $person,
        protected Totals $totals
    ) {
        $this->statistics = new ArrayCollection();
    }

    public function getViewPeriod(): ViewPeriod
    {
        return $this->viewPeriod;
    }

    public function getPerson(): BasePerson
    {
        return $this->person;
    }

    public function getLine(): int
    {
        $player = $this->getPerson()->getPlayers()->first();
        if (!($player instanceof TeamPlayer)) {
            throw new \Exception('s11player should always have a line', E_ERROR);
        }
        return $player->getLine();
    }

    /**
     * @return ArrayCollection<int|string, Statistics>|PersistentCollection<int|string, Statistics>
     * @psalm-return ArrayCollection<int|string, Statistics>
     */
    public function getStatistics(): ArrayCollection|PersistentCollection
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

//    /**
//     * @param Points $points
//     * @return array<int,int>
//     */
//    public function calculatePoints(array $seasonScoreUnits) : array
//    {
//        $totals = [];
//        foreach ($this->getGameRoundScores() as $gameRoundScore) {
//            $gameRoundScorePoints = $gameRoundScore->getPoints();
//            foreach ($seasonScoreUnits as $seasonScoreUnit) {
//                if (!array_key_exists($seasonScoreUnit->getNumber(), $totals)) {
//                    $totals[$seasonScoreUnit->getNumber()] = 0;
//                }
//                $totals[$seasonScoreUnit->getNumber()] += $gameRoundScorePoints[$seasonScoreUnit->getNumber()];
//            }
//        }
//        return $totals;
//    }

//    /**
//     * @return array<int,int>
//     */
//    public function getPoints(): array
//    {
//        return $this->points;
//    }
//
//    /**
//     * @param array<int,int> $points
//     */
//    public function setPoints(array $points): void
//    {
//        $this->points = $points;
//    }
}
