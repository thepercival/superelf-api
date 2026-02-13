<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Competition\CompetitionSport;
use Sports\Competition\CompetitionSportEditor;
use Sports\Competitor\StartLocation;
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
    public const int NrOfAgainstGamesPerRound = 3;
    protected StructureEditor $structureEditor;

    public function __construct(protected S11League $s11League)
    {
        $this->structureEditor = new StructureEditor(
            new CompetitionSportEditor(),
            new PlanningConfigService());
    }


    abstract protected function convertSportToPersistVariant(Sport $sport): PersistVariant;

    abstract public function createStructure(Competition $competition, int $nrOfValidPoolUsers): Structure;

    abstract public function createGames(Structure $structure, Pool $pool): void;

    public function createCompetition(Pool $pool, Sport $sport, PointsCalculation $pointsCalculation): Competition
    {
        $season = $pool->getSeason();
        $league = $pool->getCollection()->getLeague($this->s11League);
        if ($league === null) {
            throw new \Exception(
                'league "' . $this->s11League->name . '" not found for pool "' . $pool->getName() . '"', E_ERROR
            );
        }
        $competition = new Competition($league, $season);
        $competition->setStartDateTime($season->getStartDateTime());
        new CompetitionSport(
            $sport,
            $competition,
            $pointsCalculation,
            3, 1, 2, 1, 0,
            $this->convertSportToPersistVariant($sport)
        );
        return $competition;
    }

    /**
     * @param Competition $competition
     * @param list<PoolUser> $validPoolUsers
     * @param Structure $structure
     * @return list<Competitor>
     */
    public function createCompetitors(Competition $competition, array $validPoolUsers, Structure $structure): array
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
