<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competition\Sport\Service as CompetitionSportService;
use Sports\Competitor\StartLocation;
use Sports\League;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Ranking\PointsCalculation;
use Sports\Sport;
use Sports\Structure;
use Sports\Structure\Editor as StructureEditor;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\Competitor;
use SuperElf\League as S11League;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;

abstract class BaseCreator
{
    protected StructureEditor $structureEditor;

    public function __construct(protected S11League $s11League)
    {
        $this->structureEditor = new StructureEditor(new CompetitionSportService(), new PlanningConfigService());
    }

    public function createCompetition(Pool $pool, Sport $sport, PointsCalculation $pointsCalculation): Competition
    {
        $season = $pool->getSeason();
        $league = new League($pool->getCollection()->getAssociation(), $this->s11League->name);
        $competition = new Competition($league, $season);
        $competition->setStartDateTime($season->getStartDateTime());
        new CompetitionSport(
            $sport,
            $competition,
            $pointsCalculation,
            $this->convertSportToPersistVariant($sport)
        );
        return $competition;
    }

    abstract protected function convertSportToPersistVariant(Sport $sport): PersistVariant;

//    public function createStructureGamesAndCompetitors(Pool $pool, Structure $sourceStructure): Structure
//    {
//        $competition = $pool->getCompetition($this->s11League);
//        if ($competition === null) {
//            throw new \Exception('competition not found', E_ERROR);
//        }
//
//        // @TODO CDK
//        // check here if there are gamerounds which have started, do for assemblePeriod and TransferPeriod
//        $assemblePeriod = $pool->getAssemblePeriod();
////        foreach( $assemblePeriod->getViewPeriod()->getGameRounds() as $gameRound ) {
////            // $sourceStructure doorlopen om te kijken als er al gamerondes met $gameRound->getNumber() zijn begonnen
////            // zoja dan stoppen
////        }
//
//        $validPoolUsers = array_values($pool->getUsers()->filter(function (PoolUser $poolUser): bool {
//            return $poolUser->canCompete();
//        })->toArray());
//        $structure = $this->createStructureAndGames($competition, $assemblePeriod, $pool->getTransferPeriod(), $validPoolUsers);
//        $this->createCompetitors($competition, $validPoolUsers, $structure);
//        return $structure;
//    }

    /**
     * @param Competition $competition
     * @param list<PoolUser> $validPoolUsers
     * @param Structure $structure
     * @return list<Competitor>
     */
    protected function createCompetitors(Competition $competition, array $validPoolUsers, Structure $structure): array
    {
        $competitors = [];
        $singleCategory = $structure->getSingleCategory();
        foreach ($singleCategory->getRootRound()->getPoules() as $poule) {
            foreach ($poule->getPlaces() as $place) {
                $validPoolUser = array_shift($validPoolUsers);
                if ($validPoolUser === null) {
                    throw new \Exception('all places should have a competitor', E_ERROR);
                }
                $startLocation = new StartLocation(
                    $singleCategory->getNumber(), $place->getPouleNr(), $place->getPlaceNr()
                );
                $competitors[] = new Competitor($validPoolUser, $competition, $startLocation);
                // $this->competitorReps->save($competitor);
            }
        }
        if (count($validPoolUsers) > 0) {
            throw new \Exception('all poolusers should have a competitor', E_ERROR);
        }
        return $competitors;
    }

//    /**
//     * @param Competition $competition
//     * @param CompetitionConfig $competitionConfig,
//     * @param Structure $sourceStructure,
//     * @param list<PoolUser> $validPoolUsers
//     * @return Structure
//     */
//    abstract protected function createStructureAndGames(
//        Competition $competition,
//        CompetitionConfig $competitionConfig,
//        Structure $sourceStructure,
//        array $validPoolUsers
//    ): Structure;
}
