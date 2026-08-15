<?php

declare(strict_types=1);

namespace SuperElf\Game;

use Sports\Game\Participation;
use SportsHelpers\Identifiable;

final class ParticipationStatistics extends Identifiable
{
    /**
     * @param list<array{name: string, statistics: list<array{name: string, value: int|float}>}> $categories
     */
    public function __construct(
        protected Participation $gameParticipation,
        protected array $categories,
        protected bool $manOfTheMatch
    ) {
    }

    public function getGameParticipation(): Participation
    {
        return $this->gameParticipation;
    }

    /**
     * @return list<array{name: string, statistics: list<array{name: string, value: int|float}>}>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function isManOfTheMatch(): bool
    {
        return $this->manOfTheMatch;
    }
}