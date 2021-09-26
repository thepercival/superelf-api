<?php
declare(strict_types=1);

namespace SuperElf\Period\View\Person;

use SuperElf\Period\View\Person as ViewPeriodPerson;
use SuperElf\GameRound;
use SuperElf\GameRound\Score as BaseGameRoundScore;
use SuperElf\Pool;

class GameRoundScore extends BaseGameRoundScore {
    protected ViewPeriodPerson $viewPeriodPerson;
    protected array $stats = [];

    public function __construct(ViewPeriodPerson $viewPeriodPerson, GameRound $gameRound )
    {
        parent::__construct($gameRound);
        $this->setViewPeriodPerson( $viewPeriodPerson );
    }

    public function getViewPeriodPerson(): ViewPeriodPerson {
        return $this->viewPeriodPerson;
    }

    protected function setViewPeriodPerson(ViewPeriodPerson $viewPeriodPerson): void
    {
        if (!$viewPeriodPerson->getGameRoundScores()->contains($this)) {
            $viewPeriodPerson->getGameRoundScores()->add($this) ;
        }
        $this->viewPeriodPerson = $viewPeriodPerson;
    }

    /**
     * @return array<int,int|bool>
     */
    public function getStats(): array {
        return $this->stats;
    }

    /**
     * @param array<int,int|bool> $stats
     */
    public function setStats(array $stats ): void {
        $this->stats = $stats;
    }

    public function participated(): bool {
        return $this->stats[ViewPeriodPerson::LINEUP] || $this->stats[ViewPeriodPerson::SUBSTITUTE];
    }
}