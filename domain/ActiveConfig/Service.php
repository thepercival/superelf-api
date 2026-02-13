<?php

declare(strict_types=1);

namespace SuperElf\ActiveConfig;

use Sports\Repositories\CompetitionRepository;
use Sports\Repositories\SeasonRepository;
use Sports\Repositories\SportRepository;

final class Service
{
    public function __construct(
        protected CompetitionRepository $competitionRepos,
        protected SeasonRepository $seasonRepos,
        protected SportRepository $sportRepos,

    ) {
    }

//    public function getConfig(): ActiveConfig
//    {
//        $activeConfig = new ActiveConfig(
//            $this->getSourceCompetition(),
//        );
//        $formations = [];
//        /** @var string $formationName */
//        foreach ($this->config->getArray('availableFormations') as $formationName) {
//            $formation = new Formation();
//            new FormationLine($formation, SportCustom::Football_Line_GoalKepeer, (int) substr($formationName, 0, 1));
//            new FormationLine($formation, SportCustom::Football_Line_Defense, (int) substr($formationName, 2, 1));
//            new FormationLine($formation, SportCustom::Football_Line_Midfield, (int) substr($formationName, 4, 1));
//            new FormationLine($formation, SportCustom::Football_Line_Forward, (int) substr($formationName, 6, 1));
//            $formations[] = $formation;
//        }
//        $activeConfig->setAvailableFormations($formations);
//        return $activeConfig;
//    }
//
//    /**
//     * @return list<array<string, int|string|null>>
//     * @throws Exception
//     */
//    protected function getSourceCompetitions(): array
//    {
//        $season = $this->getSeason();
//        $sport = $this->sportRepos->findOneBy(["customId" => SportCustom::Football ]);
//        if ($sport === null) {
//            return [];
//        }
//        $competitions =  $this->competitionRepos->findExt($sport, $season->getPeriod());
//        return array_map(function (Competition $competition): array {
//            return ["id" => $competition->getId(), "name" => $competition->getName() ];
//        }, $competitions);
//    }
//

}
