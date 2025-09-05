<?php

namespace SuperElf\LineupItem;

use Sports\Game\Participation;
use Sports\Game\Participation as GameParticipation;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Team\Player\Repository as TeamPlayerRepository;
use SuperElf\LineupItem;

final class Converter
{
    public function __construct(protected TeamPlayerRepository $teamPlayerRepository)
    {
    }

    /**
     * @param AgainstGamePlace $gamePlace
     * @return list<LineupItem>
     */
    public function convert(AgainstGamePlace $gamePlace): array
    {
        $starting = $gamePlace->getParticipations(function (Participation $participation): bool {
            return $participation->isStarting();
        })->toArray();
        $subs = $gamePlace->getParticipations(function (Participation $participation): bool {
            return !$participation->isSubstituted();
        })->toArray();
        return $this->convertHelper(array_values($starting), array_values($subs));
    }

    /**
     * @param list<GameParticipation> $starting
     * @param list<GameParticipation> $subs
     * @return list<LineupItem>
     */
    public function convertHelper(array $starting, array $subs): array
    {
        $lineupItems = [];
        foreach ($starting as $startingIt) {
            $substitute = null;
            if ($startingIt->isSubstituted()) {
                $substituteParticipation = $this->removeSub($startingIt, $subs);
                $subsub = null;
                if ($substituteParticipation->isSubstituted()) {
                    $subsubParticipation = $this->removeSub($startingIt, $subs);
                    $subsub = new Substitute(
                        $subsubParticipation->getBeginMinute(),
                        $substituteParticipation->getPlayer(),
                        null
                    );
                }
                $substitute = new Substitute(
                    $substituteParticipation->getBeginMinute(),
                    $substituteParticipation->getPlayer(),
                    $subsub
                );
            }
            $lineupItems[] = new LineupItem($startingIt->getPlayer(), $substitute);
        }

        // sort by line
        uasort(
            $lineupItems,
            function (LineupItem $lineup1, LineupItem $lineup2): int {
                return $lineup1->getPlayer()->getLine() - $lineup2->getPlayer()->getLine();
            }
        );
        return array_values($lineupItems);
    }

    /**
     * @param GameParticipation $participation
     * @param list<GameParticipation> $subs
     * @return GameParticipation
     */
    protected function removeSub(GameParticipation $substituted, array &$subs): GameParticipation
    {
        foreach ($subs as $sub) {
            if ($sub->getBeginMinute() === $substituted->getEndMinute()) {
                $idx = array_search($sub, $subs);
                if ($idx !== false) {
                    array_splice($subs, $idx, 1);
                    return $sub;
                }
            }
        }
        throw new \Exception('substitute not found', E_ERROR);
    }


//    /**
//     * @param TeamCompetitor|null $teamCompetitor
//     * @return list<Participation>
//     */
//    public function getSubstituted(TeamCompetitor $teamCompetitor = null): array
//    {
//        $substituted = $this->getParticipations(function (Participation $participation) use ($teamCompetitor): bool {
//            return ($teamCompetitor === null || $participation->getPlayer()->getTeam() === $teamCompetitor->getTeam())
//                && $participation->isSubstituted();
//        })->toArray();
//        uasort($substituted, function (Participation $participationA, Participation $participationB): int {
//            return $participationA->getEndMinute() < $participationB->getEndMinute() ? -1 : 1;
//        });
//        return array_values($substituted);
//    }
}
