<?php
declare(strict_types=1);

namespace SuperElf\Pool\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use SuperElf\Pool;
use SuperElf\Season\ScoreUnit as SeasonScoreUnit;
use SuperElf\GameRound;
use SuperElf\Period\View\Person as BaseViewPeriodPerson;
use SuperElf\Pool\User\ViewPeriodPerson\Participation;
use SuperElf\Pool\User as PoolUser;

class ViewPeriodPerson extends Identifiable {
    protected PoolUser $poolUser;
    protected BaseViewPeriodPerson $viewPeriodPerson;
    protected int $total = 0;
    /**
     * @var array<int, int>
     */
    protected array $points = [];
    /**
     * @var ArrayCollection<int|string, Participation>|PersistentCollection<int|string, Participation>
     */
    protected ArrayCollection|PersistentCollection $participations;

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
     * @return ArrayCollection<int|string, Participation>|PersistentCollection<int|string, Participation>
     */
    public function getParticipations(): ArrayCollection|PersistentCollection {
        return $this->participations;
    }

    public function getParticipation( GameRound $gameRound ): ?Participation {
        $filtered = $this->participations->filter( function ( Participation $participation ) use( $gameRound): bool {
            return $participation->getGameRound() === $gameRound;
        });
        $firstParticipation = $filtered->first();
        return $firstParticipation === false ? null : $firstParticipation;
    }

    /**
     * @param list<SeasonScoreUnit> $seasonScoreUnits
     * @return array<int,int>
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

    /**
     * @return Collection<string|int,GameRoundScore>
     */
    public function getGameRoundScores(): Collection {
        return $this->viewPeriodPerson->getGameRoundScores()->filter( function ( GameRoundScore $gameRoundScore ): bool {
            return $this->getParticipation( $gameRoundScore->getGameRound() ) !== null;
        });
    }

    public function getTotal(): int {
        return $this->total;
    }

    public function setTotal(int $total): void {
        $this->total = $total;
    }

    /**
     * @return array<int, int>
     */
    public function getPoints(): array {
        return $this->points;
    }

    /**
     * @param array<int, int> $points
     */
    public function setPoints(array $points ): void {
        $this->points = $points;
    }
}