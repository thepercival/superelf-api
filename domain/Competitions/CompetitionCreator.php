<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Sport;
use Sports\Structure;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\League as S11League;
use SuperElf\Periods\ViewPeriod;
use SuperElf\Pool;

final class CompetitionCreator extends BaseCreator
{
    public function __construct()
    {
        parent::__construct(S11League::Competition);
    }

    #[\Override]
    protected function convertSportToPersistVariant(Sport $sport): PersistVariant
    {
        return $sport->createTogetherPersistVariant(
            GameMode::AllInOneGame,
            0,
            1
        );
    }

    #[\Override]
    public function createStructure(Competition $competition, int $nrOfValidPoolUsers): Structure
    {
        $structure = $this->structureEditor->create($competition, [$nrOfValidPoolUsers]);
        return $structure;
    }

    #[\Override]
    public function createGames(Structure $structure, Pool $pool): void
    {
        $poule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();
        $competitionSport = $poule->getCompetition()->getSingleSport();
        $createGames = function (ViewPeriod $viewPeriod) use ($poule, $competitionSport): void {
            foreach ($viewPeriod->getGameRounds() as $gameRound) {
                $game = new TogetherGame(
                    $poule,
                    $gameRound->getNumber(),
                    $viewPeriod->getStartDateTime(),
                    $competitionSport
                );
                foreach ($poule->getPlaces() as $place) {
                    new TogetherGamePlace($game, $place, $gameRound->getNumber());
                }
            }
        };
        $createGames($pool->getCompetitionConfig()->getAssemblePeriod()->getViewPeriod());
        $createGames($pool->getCompetitionConfig()->getTransferPeriod()->getViewPeriod());
    }

//    protected function createTogetherGame(
//        Poule $poule,
//        AgainstPlanningGame $planningGame,
//        DateTimeImmutable $startDateTime,
//        CompetitionSport $competitionSport,
//        PlanningMapper $mapper
//    ): AgainstGame {
//        $game = new TogetherGame()(
//            $poule,
//            $planningGame->getBatchNr(),
//            $startDateTime,
//            $competitionSport,
//            $planningGame->getGameRoundNumber()
//        );
//        foreach ($planningGame->getPlaces() as $planningGamePlace) {
//            $this->createAgainstGamePlace($game, $planningGamePlace, $mapper);
//        }
//        return $game;
//    }
}
