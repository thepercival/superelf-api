<?php
declare(strict_types=1);

namespace SuperElf\Period\View;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use SuperElf\Pool;
use SuperElf\Season\ScoreUnit as SeasonScoreUnit;
use Sports\Person as BasePerson;
use SuperElf\GameRound;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\View\Person\GameRoundScore;

class Person extends Identifiable {
    public const SHEET_SPOTTY_THRESHOLD = 4;

    public const RESULT = 1;
    public const GOALS_FIELD = 2;
    public const GOALS_PENALTY = 4;
    public const GOALS_OWN = 8;
    public const ASSISTS = 16;
    public const SHEET_CLEAN = 32;
    public const SHEET_SPOTTY = 64;
    public const CARDS_YELLOW = 128;
    public const CARD_RED = 256;
    public const LINEUP = 512;
    public const SUBSTITUTED = 1024;
    public const SUBSTITUTE = 2048;
    public const LINE = 4096;

    protected int $total = 0;
    /**
     * @var array<int,int>
     */
    protected array $points = [];
    /**
     * @var ArrayCollection<int|string, GameRoundScore>|PersistentCollection<int|string, GameRoundScore>
     */
    protected ArrayCollection|PersistentCollection $gameRoundScores;

    public function __construct( protected ViewPeriod $viewPeriod, protected BasePerson $person )
    {
        $this->gameRoundScores = new ArrayCollection();
    }

    public function getViewPeriod(): ViewPeriod {
        return $this->viewPeriod;
    }

    public function getPerson(): BasePerson {
        return $this->person;
    }

    /**
     * @return ArrayCollection<int|string, GameRoundScore>|PersistentCollection<int|string, GameRoundScore>
     */
    public function getGameRoundScores(): ArrayCollection|PersistentCollection {
        return $this->gameRoundScores;
    }

    public function getGameRoundScore( GameRound $gameRound ): ?GameRoundScore {
        $filtered = $this->gameRoundScores->filter( function ( GameRoundScore $gameRoundScore ) use( $gameRound): bool {
            return $gameRoundScore->getGameRound() === $gameRound;
        });
        $firstScore = $filtered->first();
        return $firstScore === false ? null : $firstScore;
    }

    public function getTotal(): int {
        return $this->total;
    }

    public function setTotal(int $total): void {
        $this->total = $total;
    }

    /**
     * @param list<SeasonScoreUnit> $seasonScoreUnits
     * @return array<int,int>
     */
    public function calculatePoints(array $seasonScoreUnits) : array {
        $totals = [];
        foreach( $this->getGameRoundScores() as $gameRoundScore ) {
            $gameRoundScorePoints = $gameRoundScore->getPoints();
            foreach ($seasonScoreUnits as $seasonScoreUnit) {
                if (!array_key_exists($seasonScoreUnit->getNumber(), $totals)) {
                    $totals[$seasonScoreUnit->getNumber()] = 0;
                }
                $totals[$seasonScoreUnit->getNumber()] += $gameRoundScorePoints[$seasonScoreUnit->getNumber()];
            }
        }
        return $totals;
    }

    /**
     * @return array<int,int>
     */
    public function getPoints(): array {
        return $this->points;
    }

    /**
     * @param array<int,int> $points
     */
    public function setPoints(array $points ): void {
        $this->points = $points;
    }
}