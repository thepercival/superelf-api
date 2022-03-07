<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Sport;
use Sports\Structure;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\Competitor;
use SuperElf\League as S11League;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Pool\User as PoolUser;

class CupCreator extends BaseCreator
{
    public function __construct()
    {
        parent::__construct(S11League::Cup);
    }

    protected function convertSportToPersistVariant(Sport $sport): PersistVariant
    {
        return $sport->createAgainstPersistVariant(3, 1);
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

    /**
     * @param Competition $competition
     * @param AssemblePeriod $assemblePeriod ,
     * @param TransferPeriod $transferPeriod ,
     * @param list<Competitor> $competitors
     */
    protected function createGames(
        Competition $competition,
        AssemblePeriod $assemblePeriod,
        TransferPeriod $transferPeriod,
        array $competitors
    ): void {
//        $gameRounds = $assemblePeriod->getViewPeriod()->getGameRounds();
//        // calculate nice verdeling CDK
//        if (count($gameRounds) < THISH2H + 1) {
//            throw new \Exception('assemble-viewperiod should have at least THISH2H gamerounds', E_ERROR);
//        }
//
//        $createGames = function (ViewPeriod $viewPeriod) use ($competition, $poule, $competitors): void {
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
    }
}
