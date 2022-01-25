<?php

declare(strict_types=1);

namespace SuperElf\Competitions;

use Sports\Competition;
use Sports\Competition\Sport\Service as CompetitionSportService;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Poule;
use Sports\Sport;
use Sports\Structure\Editor as StructureEditor;
use SportsHelpers\Sport\PersistVariant;
use SuperElf\CompetitionType;
use SuperElf\Competitor;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Pool;

class CupCreator extends BaseCreator
{
    public function __construct()
    {
        parent::__construct(CompetitionType::CUP);
    }

    protected function convertSportToPersistVariant(Sport $sport): PersistVariant
    {
        return $sport->createAgainstPersistVariant(3, 1);
    }

    protected function createStructure(Competition $competition, Pool $pool): Poule
    {
        $structureEditor = new StructureEditor(
            new CompetitionSportService(),
            new PlanningConfigService()
        );
        // kijk hier hoe je de knockoutrondes indeelt
        $structure = $structureEditor->create($competition, [$pool->getUsers()->count()]);
        return $structure->getRootRound()->getPoule(1);
    }

    /**
     * @param Competition $competition
     * @param Pool $pool
     * @return list<Competitor>
     */
    protected function createCompetitors(Competition $competition, Pool $pool): array
    {
        $placeNr = 1;
        $competitors = [];
        foreach ($pool->getUsers() as $poolUser) {
            $competitors[] = new Competitor($poolUser, $competition, 1, $placeNr++);
            // $this->competitorReps->save($competitor);
        }
        return $competitors;
    }

    /**
     * @param Competition $competition
     * @param Pool $pool
     * @param Poule $poule
     * @param list<Competitor> $competitors
     */
    protected function createGames(Competition $competition, Pool $pool, Poule $poule, array $competitors): void
    {
        $createGames = function (ViewPeriod $viewPeriod) use ($competition, $poule, $competitors): void {
            $competitionSport = $competition->getSingleSport();
            foreach ($viewPeriod->getGameRounds() as $gameRound) {
                $game = new TogetherGame(
                    $poule,
                    $gameRound->getNumber(),
                    $competition->getStartDateTime(),
                    $competitionSport
                );
                foreach ($competitors as $competitor) {
                    $place = $poule->getPlace($competitor->getPlaceNr());
                    new TogetherGamePlace($game, $place, $gameRound->getNumber());
                }
            }
        };
        $createGames($pool->getAssemblePeriod()->getViewPeriod());
        $createGames($pool->getTransferPeriod()->getViewPeriod());
    }
}
