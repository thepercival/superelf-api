<?php

namespace SuperElf\GameRound;

use SuperElf\Formation as S11Formation;
use SuperElf\Formation\Line as S11FormationLine;
use SuperElf\GameRound;
use SuperElf\Periods\ViewPeriod;
use SuperElf\Pool;
use SuperElf\Totals as TotalsBase;
use SuperElf\Totals\FormationLine as GameRoundFormationLineTotals;
use SuperElf\Totals\PoolUser as PoolUserTotals;

class TotalsCalculator
{
    public function __construct() {
    }

    /**
     * @param Pool $pool
     * @param ViewPeriod $viewPeriod
     * @param GameRound|null $gameRound
     * @return list<PoolUserTotals>
     */
    public function calculatePoolUsers(Pool $pool, ViewPeriod $viewPeriod, GameRound|null $gameRound): array {
        $totals = [];
        foreach( $pool->getUsers() as $poolUser) {
            $formation = $poolUser->getFormation($viewPeriod);
            if( $formation === null) {
                continue;
            }
            $formationLineTotals = $this->getFormationLinesTotals($formation,$gameRound);

            $totals[] = new PoolUserTotals((int)$poolUser->getId(), $formationLineTotals);
        }
        return $totals;
    }

    /**
     * @param S11Formation $formation
     * @param GameRound|null $gameRound
     * @return list<GameRoundFormationLineTotals>
     */
    protected function getFormationLinesTotals(S11Formation $formation, GameRound|null $gameRound): array {
        $totals = [];
        foreach( $formation->getLines() as $formationLine) {

            if( $gameRound === null) {
                $formationLineTotals = $formationLine->getTotals();
            } else {
                $formationLineTotals = $this->calculateFormationLineTotals($formationLine, $gameRound);
            }
            $totals[] = new GameRoundFormationLineTotals($formationLine->getLine(), $formationLineTotals);
        }
        return $totals;
    }

    protected function calculateFormationLineTotals(S11FormationLine $formationLine, GameRound $gameRound): TotalsBase {
        $totals = new TotalsBase();
        foreach( $formationLine->getPlaces() as $startingPlace) {
            $statistics = $startingPlace->getGameRoundStatistics($gameRound);
            if ($statistics !== null) {
                $totals = $totals->add($statistics);
            }
        }
        return $totals;
    }
}