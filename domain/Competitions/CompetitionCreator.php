<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Sport;
use Sports\Structure;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\League as S11League;
use SuperElf\Pool\User as PoolUser;

class CompetitionCreator extends BaseCreator
{
    public function __construct()
    {
        parent::__construct(S11League::Competition);
    }

    protected function convertSportToPersistVariant(Sport $sport): PersistVariant
    {
        return $sport->createTogetherPersistVariant(
            GameMode::AllInOneGame,
            0,
            1
        );
    }

    /**
     * @param Competition $competition
     * @param list<PoolUser> $validPoolUsers
     * @return Structure
     */
    protected function createStructure(Competition $competition, array $validPoolUsers): Structure
    {
        $structure = $this->structureEditor->create($competition, [count($validPoolUsers)]);
//        $this->createGames($competition, $assemblePeriod, $transferPeriod, $structure);
        return $structure;
    }

//    protected function createGames(Competition $competition, AssemblePeriod $assemblePeriod, TransferPeriod $transferPeriod, Structure $structure): void
//    {
//        $poule = $structure->getRootRound()->getFirstPoule();
//        $createGames = function (ViewPeriod $viewPeriod) use ($competition, $poule): void {
//            $competitionSport = $competition->getSingleSport();
//            foreach ($viewPeriod->getGameRounds() as $gameRound) {
//                $game = new TogetherGame(
//                    $poule,
//                    $gameRound->getNumber(),
//                    $competition->getStartDateTime(),
//                    $competitionSport
//                );
//                foreach ($poule->getPlaces() as $place) {
//                    new TogetherGamePlace($game, $place, $gameRound->getNumber());
//                }
//            }
//        };
//        $createGames($assemblePeriod->getViewPeriod());
//        $createGames($transferPeriod->getViewPeriod());
//    }
}
