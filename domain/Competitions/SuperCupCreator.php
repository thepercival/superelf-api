<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Sport;
use Sports\Structure;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\League as S11League;

class SuperCupCreator extends BaseCreator
{
    public function __construct()
    {
        parent::__construct(S11League::SuperCup);
    }

    protected function convertSportToPersistVariant(Sport $sport): PersistVariant
    {
        return $sport->createAgainstPersistVariant(3, 1);
    }

    /**
     * @param Competition $competition
     * @return Structure
     */
    protected function createStructure(Competition $competition): Structure
    {
        $structure = $this->structureEditor->create($competition, [2]);
//        $this->createGames($competition, $assemblePeriod, $transferPeriod, $structure);
        return $structure;
    }

//    protected function createGames(
//        Competition $competition,
//        CompetitionConfig $competitionConfig,
//        Structure $structure,
//        Structure $sourceStructure
//    ): void
//    {
//        $poule = $structure->getRootRound()->getFirstPoule();
//
//        $competitionSport = $competition->getSingleSport();
//
//        $assemblePeriod = $competitionConfig->getAssemblePeriod();
//        $gameRounds = $assemblePeriod->getViewPeriod()->getGameRounds();
//
//        $startAfterNrOfGameRounds = 1;
//        $nrOfH2h = 3;
//        $minNrOfGameRounds = $startAfterNrOfGameRounds + $nrOfH2h;
//        if (count($gameRounds) < $minNrOfGameRounds) {
//            throw new \Exception('assemble-viewperiod should have at least ' . $minNrOfGameRounds . ' gamerounds', E_ERROR);
//        }
//        // @TODO CDK BEPAAL WELKE RONDEN ER GEBRUIKT MOETEN WORDEN VOOR CUP EN SUPERCUP
//        $batchNr = 0;
//        foreach ($gameRounds as $gameRound) {
//            if ($startAfterNrOfGameRounds-- > 0) {
//                continue;
//            }
//            $game = new AgainstGame(
//                $poule,
//                ++$batchNr,
//                $assemblePeriod->getViewPeriod()->getStartDateTime()->modify('+' . $batchNr . ' days'),
//                $competitionSport,
//                $gameRound->getNumber(),
//            );
//            if (($batchNr % 2) === 0) {
//                $homePlace = $poule->getPlace(1);
//                $awayPlace = $poule->getPlace(2);
//            } else {
//                $homePlace = $poule->getPlace(2);
//                $awayPlace = $poule->getPlace(1);
//            }
//            new AgainstGamePlace($game, $homePlace, Side::Home);
//            new AgainstGamePlace($game, $awayPlace, Side::Away);
//        }
//    }

//    protected function getAvailableGameRounds($sourceStructure): array {
//        // wanneer er gamerounds veranderen
//        // gamerounds van wedstrijden die
//        // 1 allemaal na nu zijn
//        // 2 nog niet begonnen is
//        // wat doe je met gamerounds die overlappen met latere gamerounds?
//        // aanzetten na migratie
//
//    }
}
