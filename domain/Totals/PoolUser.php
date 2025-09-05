<?php

namespace SuperElf\Totals;

use SuperElf\Totals\FormationLine as FormationLineTotals;

/**
 * @psalm-api
 */
final class PoolUser
{
    /**
     * @param int $poolUserId
     * @param list<FormationLineTotals> $formationLineTotals
     */
    public function __construct(
        protected int $poolUserId,
        protected array $formationLineTotals) {
    }

    public function getPoolUserId(): int {
        return $this->poolUserId;
    }

    /**
     * @return list<FormationLineTotals>
     */
    public function getFormationLineTotals(): array {
        return $this->formationLineTotals;
    }
}