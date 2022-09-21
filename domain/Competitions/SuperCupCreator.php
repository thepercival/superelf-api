<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Round;
use Sports\Sport;
use Sports\Structure;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\League as S11League;
use SuperElf\Pool;

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

    public function createStructure(Competition $competition, int $nrOfValidPoolUsers): Structure
    {
        $structure = $this->structureEditor->create($competition, [2]);
        $round = $structure->getSingleCategory()->getRootRound();
        $this->updateAgainstQualifyConfig($round, $competition->getSingleSport());
        return $structure;
    }

    protected function updateAgainstQualifyConfig(Round $rootRound, CompetitionSport $competitionSport): void
    {
        $againstQualifyConfig = $rootRound->getAgainstQualifyConfig($competitionSport);
        if ($againstQualifyConfig === null) {
            return;
        }
        $againstQualifyConfig->setWinPoints(1);
        $againstQualifyConfig->setWinPointsExt(0);
        $againstQualifyConfig->setDrawPointsExt(0);
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

    public function createGames(Structure $structure, Pool $pool): void
    {
        $assembleViewPeriod = $pool->getCompetitionConfig()->getAssemblePeriod()->getViewPeriod();
        $gameRounds = $assembleViewPeriod->getGameRounds()->toArray();

        // first gameRoundNumber no supercup
        array_shift($gameRounds);

        $nrOfRounds = 1;
        $poule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();
        $competitionSport = $poule->getCompetition()->getSingleSport();
        $places = $poule->getPlaces()->toArray();
        $homePlace = array_shift($places);
        $awayPlace = array_pop($places);
        if ($homePlace === null || $awayPlace === null) {
            throw new \Exception('not enough places', E_ERROR);
        }
        while ($nrOfRounds++ <= self::NrOfAgainstGamesPerRound) {
            $gameRound = array_shift($gameRounds);
            if ($gameRound === null) {
                throw new \Exception('not enough roundnumbers to schedule supercup', E_ERROR);
            }

            $game = new AgainstGame(
                $poule,
                $nrOfRounds,
                $assembleViewPeriod->getStartDateTime(),
                $competitionSport,
                $gameRound->getNumber()
            );
            new AgainstGamePlace($game, $homePlace, Side::Home);
            new AgainstGamePlace($game, $awayPlace, Side::Away);
        }
    }

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
