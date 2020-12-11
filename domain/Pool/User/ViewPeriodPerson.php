<?php

declare(strict_types=1);

namespace SuperElf\Pool\User;

use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Season\ScoreUnit as SeasonScoreUnit;
use SuperElf\GameRound;
use SuperElf\Period\View\Person as BaseViewPeriodPerson;
use SuperElf\Pool\User\ViewPeriodPerson\Participation;
use SuperElf\Pool\User as PoolUser;

class ViewPeriodPerson {
    protected int $id;
    protected PoolUser $poolUser;
    protected BaseViewPeriodPerson $viewPeriodPerson;
    protected int $total = 0;
    /**
     * @var array | int[]
     */
    protected array $points = [];
    /**
     * @var ArrayCollection | Participation[]
     */
    protected $participations;

    public function __construct( PoolUser $poolUser, BaseViewPeriodPerson $viewPeriodPerson )
    {
        $this->poolUser = $poolUser;
        $this->viewPeriodPerson = $viewPeriodPerson;
        $this->participations = new ArrayCollection();
    }

    public function getPoolUser(): PoolUser {
        return $this->poolUser;
    }

    public function getViewPeriodPerson(): BaseViewPeriodPerson {
        return $this->viewPeriodPerson;
    }

    /**
     * @return ArrayCollection | Participation[]
     */
    public function getParticipations() {
        return $this->participations;
    }

    public function getParticipation( GameRound $gameRound ): ?Participation {
        $filtered = $this->participations->filter( function ( Participation $participation ) use( $gameRound): bool {
            return $participation->getGameRound() === $gameRound;
        });
        return $filtered->count() > 0 ? $filtered->first() : null;
    }

    /**
     * @param array|SeasonScoreUnit[] $seasonScoreUnits
     * @return array|int[]
     */
    public function calculatePoints($seasonScoreUnits) : array {
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

    public function getGameRoundScores() {
        $filtered = $this->viewPeriodPerson->getGameRoundScores()->filter( function ( GameRoundScore $gameRoundScore ): bool {
            return $this->getParticipation( $gameRoundScore->getGameRound() ) !== null;
        });
        return $filtered->count() > 0 ? $filtered->first() : null;
    }

    public function getTotal(): int {
        return $this->total;
    }

    public function setTotal(int $total) {
        $this->total = $total;
    }

    /**
     * @return array|int[]
     */
    public function getPoints(): array {
        return $this->points;
    }

    /**
     * @param array|int[] $points
     */
    public function setPoints(array $points ) {
        $this->points = $points;
    }
}