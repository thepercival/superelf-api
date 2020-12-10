<?php

namespace SuperElf;

use DateTimeImmutable;
use Sports\Person;
use Sports\Team\Player;

class OneTeamSimultaneous {
    public function getPlayer(Person $person, DateTimeImmutable $dateTime = null): ?Player {
        $checkDateTime = $dateTime !== null ? $dateTime : new DateTimeImmutable();
        $filtered = $person->getPlayers()->filter( function(Player $player ) use ($checkDateTime) : bool {
            return $player->getPeriod()->contains($checkDateTime);
        });
        return $filtered->count() > 0 ? $filtered->first() : null;
    }
}
