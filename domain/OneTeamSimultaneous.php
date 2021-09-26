<?php
declare(strict_types=1);

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
        $firstPlayer = $filtered->first();
        return $firstPlayer === false ? null : $firstPlayer;
    }
}
