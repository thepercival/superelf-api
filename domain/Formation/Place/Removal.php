<?php

declare(strict_types=1);

namespace SuperElf\Formation\Place;

use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Player as S11Player;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Removal
{
    public function __construct(
        protected FormationPlace $formationPlace,
        protected S11player|null $player
    ) {
    }

    public function getFormationPlace(): FormationPlace
    {
        return $this->formationPlace;
    }

    public function getPlayer(): S11Player|null
    {
        return $this->player;
    }
}
