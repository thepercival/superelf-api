<?php

declare(strict_types=1);

namespace SuperElf\CompetitionCreator;

use Sports\Competition;
use Sports\Competition\Sport\Service as CompetitionSportService;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Poule;
use Sports\Sport;
use Sports\Structure\Editor as StructureEditor;
use SuperElf\CompetitionType;
use SuperElf\Competitor;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Pool;

class DefaultCreator extends MainCreator
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createCompetition(Pool $pool, Sport $sport, int $competitionType): Competition
    {
        return $this->createCompetitionHelper($pool, $sport, $competitionType);
    }

    public function recreateDetails(Pool $pool): void
    {
        $competition = $pool->getCompetition(CompetitionType::COMPETITION);
        if ($competition === null) {
            throw new \Exception('default competition for pool should always exist', E_ERROR);
        }
        $poule = $this->recreateStructure($competition, $pool);
        $competitors = $this->recreateCompetitors($competition, $pool);
        $this->recreateGames($competition, $pool, $poule, $competitors);
    }

    protected function recreateStructure(Competition $competition, Pool $pool): Poule
    {
        $structureEditor = new StructureEditor(
            new CompetitionSportService(),
            new PlanningConfigService()
        );
        $structure = $structureEditor->create($competition, [$pool->getUsers()->count()]);
        return $structure->getRootRound()->getPoule(1);
    }

    /**
     * @param Competition $competition
     * @param Pool $pool
     * @return list<Competitor>
     */
    protected function recreateCompetitors(Competition $competition, Pool $pool): array
    {
        // remove competitors and than create them
        foreach ($pool->getUsers() as $poolUser) {
            $competitor = $poolUser->getCompetitor($competition);
            if ($competitor === null) {
                continue;
            }
            $poolUser->getCompetitors()->removeElement($competitor);
            // $this->competitorReps->remove($competitor);
        }
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
    protected function recreateGames(Competition $competition, Pool $pool, Poule $poule, array $competitors): void
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
