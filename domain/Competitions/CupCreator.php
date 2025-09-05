<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Qualify\Target;
use Sports\Round;
use Sports\Sport;
use Sports\Structure;
use SportsHelpers\Against\Side;
use SportsHelpers\PouleStructure\Balanced as BalancedPouleStructure;
use SportsHelpers\PouleStructure\BalancedCreator as BalancedPouleStructureCreator;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\GameRound;
use SuperElf\League as S11League;
use SuperElf\Periods\ViewPeriod;
use SuperElf\Pool;

final class CupCreator extends BaseCreator
{
    public function __construct()
    {
        parent::__construct(S11League::Cup);
    }

    #[\Override]
    protected function convertSportToPersistVariant(Sport $sport): PersistVariant
    {
        return $sport->createAgainstPersistVariant(3, 1);
    }

    #[\Override]
    public function createStructure(Competition $competition, int $nrOfValidPoolUsers): Structure
    {
        // $nrOfRounds = $this->getNrOfRounds($nrOfValidPoolUsers);
        $nrOfQualifiers = $this->getNrOfQualifiers($nrOfValidPoolUsers);
        $firstRoundPouleStructure = $this->createPouleStructure($nrOfValidPoolUsers, $nrOfQualifiers);
        $structure = $this->structureEditor->create($competition, $firstRoundPouleStructure->toArray());
        $round = $structure->getSingleCategory()->getRootRound();
        $this->updateAgainstQualifyConfig($round, $competition->getSingleSport());
//        if( $firstRoundPouleStructure->getNrOfPlaces() % 2 === 0) {
//            $nrOfQualifiers = (int)($nrOfQualifiers / 2);
//        }
        while ($nrOfQualifiers > 1) {
            $nextNrOfQualifiers = $this->getNrOfQualifiers($nrOfQualifiers);
            $pouleStructure = $this->createPouleStructure($nrOfQualifiers, $nextNrOfQualifiers);
            $round = $this->structureEditor->addChildRound($round, Target::Winners, $pouleStructure->toArray());
            $nrOfQualifiers = $nextNrOfQualifiers;
        }
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

    #[\Override]
    public function createGames(Structure $structure, Pool $pool): void
    {
        $assembleViewPeriod = $pool->getCompetitionConfig()->getAssemblePeriod()->getViewPeriod();
        $assembleGameRounds = array_values($assembleViewPeriod->getGameRounds()->toArray());
        $transferViewPeriod = $pool->getCompetitionConfig()->getTransferPeriod()->getViewPeriod();
        $transferGameRounds = array_values($transferViewPeriod->getGameRounds()->toArray());

        $rounds = $this->getRounds($structure->getSingleCategory()->getRootRound());

        $lastRound = array_pop($rounds);
        array_pop($transferGameRounds);
        array_pop($transferGameRounds);
        $gameRounds = $this->removeGameRoundsAt($transferGameRounds, false);
        $this->createRoundGames($lastRound, $assembleViewPeriod, $gameRounds);

        $firstRound = array_shift($rounds);
        if ($firstRound === null) {
            return;
        }
        array_shift($assembleGameRounds);
        array_shift($assembleGameRounds); // supercup
        array_shift($assembleGameRounds); // supercup
        array_shift($assembleGameRounds); // supercup
        array_shift($assembleGameRounds);
        $gameRounds = $this->removeGameRoundsAt($assembleGameRounds, true);
        $this->createRoundGames($firstRound, $assembleViewPeriod, $gameRounds);

        $lastRound = array_pop($rounds);
        if ($lastRound === null) {
            return;
        }
        array_pop($transferGameRounds);
        $gameRounds = $this->removeGameRoundsAt($transferGameRounds, false);
        $this->createRoundGames($lastRound, $assembleViewPeriod, $gameRounds);

        $firstRound = array_shift($rounds);
        if ($firstRound === null) {
            return;
        }
        array_shift($assembleGameRounds);
        $gameRounds = $this->removeGameRoundsAt($assembleGameRounds, true);
        $this->createRoundGames($firstRound, $assembleViewPeriod, $gameRounds);

        $lastRound = array_pop($rounds);
        if ($lastRound === null) {
            return;
        }
        array_pop($transferGameRounds);
        $gameRounds = $this->removeGameRoundsAt($transferGameRounds, false);
        $this->createRoundGames($lastRound, $assembleViewPeriod, $gameRounds);

        $firstRound = array_shift($rounds);
        if ($firstRound === null) {
            return;
        }
        array_shift($assembleGameRounds);
        $gameRounds = $this->removeGameRoundsAt($assembleGameRounds, true);
        $this->createRoundGames($firstRound, $assembleViewPeriod, $gameRounds);
    }

    /**
     * @param Round $round
     * @return non-empty-list<Round>
     */
    protected function getRounds(Round $round): array
    {
        $rounds = [$round];
        foreach ($round->getChildren() as $childRound) {
            $grandChildRounds = $this->getRounds($childRound);
            foreach ($grandChildRounds as $grandChildRound) {
                $rounds[] = $grandChildRound;
            }
        }
        return $rounds;
    }

    /**
     * @param Round $round
     * @param ViewPeriod $viewPeriod
     * @param list<GameRound> $gameRounds
     * @throws \Exception
     */
    protected function createRoundGames(Round $round, ViewPeriod $viewPeriod, array $gameRounds): void
    {
        $competitionSport = $round->getCompetition()->getSingleSport();
        $batchNr = 1;
        foreach ($round->getPoules() as $poule) {
            foreach ($gameRounds as $gameRound) {
                $places = $poule->getPlaces()->toArray();
                $homePlace = array_shift($places);
                if ($homePlace === null) {
                    throw new \Exception('not enough places', E_ERROR);
                }
                $awayPlace = array_pop($places);
                if ($awayPlace === null) {
                    continue;
                }
                $game = new AgainstGame(
                    $poule,
                    $batchNr++,
                    $viewPeriod->getStartDateTime(),
                    $competitionSport,
                    $gameRound->getNumber()
                );
                new AgainstGamePlace($game, $homePlace, Side::Home);
                new AgainstGamePlace($game, $awayPlace, Side::Away);
            }
        }
    }

    /**
     * @param list<GameRound> $gameRounds
     * @param bool $start
     * @return list<GameRound>
     */
    protected function removeGameRoundsAt(array &$gameRounds, bool $start): array
    {
        $removedGameRounds = [];
        for ($i = 1; $i <= self::NrOfAgainstGamesPerRound; $i++) {
            if ($start) {
                $removedGameRound = array_shift($gameRounds);
                if ($removedGameRound !== null) {
                    array_push($removedGameRounds, $removedGameRound);
                }
            } else {
                $removedGameRound = array_pop($gameRounds);
                if ($removedGameRound !== null) {
                    array_unshift($removedGameRounds, $removedGameRound);
                }
            }
        }
        if (count($removedGameRounds) !== self::NrOfAgainstGamesPerRound) {
            throw new \Exception('not enough gameRounds', E_ERROR);
        }
        return $removedGameRounds;
    }

    protected function getNrOfQualifiers(int $nrOfCompetitors): int
    {
        if ($nrOfCompetitors > 64) {
            throw new \Exception('too many competitors', E_ERROR);
        }
        $nrOfQualifiers = 32;
        while ($nrOfCompetitors <= $nrOfQualifiers) {
            if ($nrOfQualifiers === 1) {
                return 0;
            }
            $nrOfQualifiers = (int)($nrOfQualifiers / 2);
        }
        return $nrOfQualifiers;
    }

    protected function createPouleStructure(int $nrOfPlaces, int $nextNrOfPlaces): BalancedPouleStructure
    {
        $creator = new BalancedPouleStructureCreator();
        $nrOfPoules = $this->calculateNrOfPoules($nrOfPlaces, $nextNrOfPlaces);
        return $creator->createBalanced($nrOfPlaces, $nrOfPoules);
    }

    protected function calculateNrOfPoules(int $nrOfPlaces, int $nextNrOfPlaces): int
    {
        $nrOfPoules = 0;
        $nrOfPlacesPerPoule = 2;
        while ($nrOfPlaces > 0) {
            $nrOfPoules++;
            $nrOfPlaces -= $nrOfPlacesPerPoule;
            if($nrOfPlacesPerPoule > 1 && ($nrOfPlaces + $nrOfPoules) === $nextNrOfPlaces) {
                $nrOfPlacesPerPoule--;
            }
        }
        return $nrOfPoules;
    }

//    protected function calculateNrOfRounds(int $nrOfCompetitors): int
//    {
//        $nrOfRounds = 0;
//        $nrOfPlaces = 1;
//        while ($nrOfPlaces <= $nrOfCompetitors) {
//            $nrOfRounds++;
//            $nrOfPlaces = (int)($nrOfPlaces * 2);
//        }
//        return $nrOfRounds;
//    }
}
