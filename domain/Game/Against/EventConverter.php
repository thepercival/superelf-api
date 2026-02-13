<?php

namespace SuperElf\Game\Against;

use Sports\Competitor\StartLocationMap;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Repositories\TeamPlayerRepository;
use Sports\Competitor\Team as TeamCompetitor;
use SuperElf\FootballScore;
use SuperElf\Game\Against\GoalEvent as AgainstGameGoalEvent;
use SuperElf\Game\Against\CardEvent as AgainstGameCardEvent;

final class EventConverter
{
    public function __construct(protected TeamPlayerRepository $teamPlayerRepository)
    {
    }



    /**
     * @param AgainstGamePlace $gamePlace
     * @return list<AgainstGameGoalEvent|AgainstGameCardEvent>
     */
    public function convert(AgainstGamePlace $gamePlace): array
    {
        $events = $this->getGoalAndCardEvents($gamePlace);
        return $this->convertHelper($events);
    }

    /**
     * @param AgainstGamePlace $gamePlace
     * @return list<GoalEvent|CardEvent>
     */
    protected function getGoalAndCardEvents(AgainstGamePlace $gamePlace): array
    {
        $place = $gamePlace->getPlace();
        $competition = $place->getPoule()->getCompetition();
        $competitors = array_values($competition->getTeamCompetitors()->toArray());
        $startLocationMap = new StartLocationMap($competitors);
        $startLocation = $place->getStartLocation();
        if( $startLocation === null ) {
            return [];
        }
        /** @var TeamCompetitor|null $competitor */
        $competitor = $startLocationMap->getCompetitor($startLocation);
        if( $competitor === null ) {
            return [];
        }
        return array_merge(
            $gamePlace->getGoalEvents($competitor),
            $gamePlace->getCardEvents($competitor)
        );
    }

    /**
     * @param list<GoalEvent|CardEvent> $events
     * @return list<AgainstGameGoalEvent|AgainstGameCardEvent>
     */
    public function convertHelper(array $events): array
    {
        $againstGameEvents = array_map( function(GoalEvent|CardEvent $event): AgainstGameGoalEvent|AgainstGameCardEvent {
            if ($event instanceof GoalEvent ) {
                return new AgainstGameGoalEvent(
                    $event->getGameParticipation()->getPlayer(),
                    $event->getMinute(),
                    FootballScore::fromGoal($event->getPenalty(), $event->getOwn()),
                    $event->getAssistGameParticipation()?->getPlayer()
                );
            }
            return new AgainstGameCardEvent(
                $event->getGameParticipation()->getPlayer(),
                $event->getMinute(),
                FootballScore::fromCard($event->getType())
            );
        }, $events );

        uasort(
            $againstGameEvents,
            function (
                AgainstGameGoalEvent|AgainstGameCardEvent $event1,
                AgainstGameGoalEvent|AgainstGameCardEvent $event2): int {
                return $event1->getMinute() - $event2->getMinute();
            }
        );
        return array_values($againstGameEvents);
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
